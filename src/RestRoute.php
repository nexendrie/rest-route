<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use Nexendrie\RestRoute\Support\Inflector;
use Nette\Http\UrlScript;
use Nette\InvalidArgumentException;
use Nette\Http\IRequest;
use Nette\InvalidStateException;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * @author Adam Štipák <adam.stipak@gmail.com>
 */
class RestRoute implements \Nette\Routing\Router
{
    use SmartObject;

    public const MODULE_VERSION_PATH_PREFIX_PATTERN = '/v[0-9\.]+/';

    public const KEY_PRESENTER = 'presenter';

    public const KEY_ACTION = 'action';

    public const KEY_METHOD = 'method';

    public const KEY_POST = 'post';

    public const KEY_FILES = 'files';

    public const KEY_ASSOCIATIONS = 'associations';

    public const KEY_QUERY = 'query';

    protected string $path;

    protected ?string $module;

    protected string $versionRegex;

    protected bool $useURLModuleVersioning = false;

    /**
     * @var array<string|null, string>
     */
    protected array $versionToModuleMapping;

    /**
     * @var array<string, string>
     */
    protected array $formats = [
        'json' => 'application/json',
        'xml' => 'application/xml',
    ];

    protected string $defaultFormat;

    public function __construct(?string $module = null, string $defaultFormat = 'json')
    {
        if (!array_key_exists($defaultFormat, $this->formats)) {
            throw new InvalidArgumentException("Format '{$defaultFormat}' is not allowed.");
        }

        $this->module = $module;
        $this->defaultFormat = $defaultFormat;
    }

    /**
     * @param array<string|null, string> $moduleMapping
     */
    public function useURLModuleVersioning(string $versionRegex, array $moduleMapping): self
    {
        $this->useURLModuleVersioning = true;
        $this->versionRegex = $versionRegex;
        $this->versionToModuleMapping = $moduleMapping;
        return $this;
    }

    public function getDefaultFormat(): string
    {
        return $this->defaultFormat;
    }

    public function getPath(): string
    {
        $path = implode('/', explode(':', (string) $this->module));
        $this->path = Strings::lower($path);

        return $this->path;
    }

    /**
     * Maps HTTP request to a Request object.
     * @return array{presenter: string, action: string|null, method: string, post: mixed[], files: mixed[], secured: bool, id?: string|null, format: string, associations: array<string, mixed>, data: string, query: mixed[]}
     */
    public function match(IRequest $httpRequest): ?array
    {
        $url = $httpRequest->getUrl();
        $basePath = Strings::replace($url->getBasePath(), '/\//', '\/');
        $cleanPath = Strings::replace($url->getPath(), "/^{$basePath}/");

        $path = Strings::replace($this->getPath(), '/\//', '\/');
        $pathRexExp = empty($path) ? "/^.+$/" : "/^{$path}\/.*$/";

        if (!Strings::match($cleanPath, $pathRexExp)) {
            return null;
        }

        $cleanPath = Strings::replace($cleanPath, '/^' . $path . '\//');

        $params = [];
        $path = $cleanPath;
        $params['action'] = $this->detectAction($httpRequest);
        $frags = explode('/', $path);

        if ($this->useURLModuleVersioning) {
            $version = array_shift($frags);
            if (!Strings::match($version, $this->versionRegex)) {
                array_unshift($frags, $version);
                $version = null;
            }
        }

        // Resource ID.
        if (count($frags) % 2 === 0) {
            $params['id'] = array_pop($frags);
        } elseif ($params['action'] === 'read') {
            $params['action'] = 'readAll';
        }
        $presenterName = Inflector::studlyCase(array_pop($frags));

        // Allow to use URLs like domain.tld/presenter.format.
        $formats = join('|', array_keys($this->formats));
        if (Strings::match($presenterName, "/.+\.({$formats})$/")) {
            list($presenterName) = explode('.', $presenterName);
        }

        // Associations.
        $assoc = [];
        if (count($frags) > 0 && count($frags) % 2 === 0) {
            foreach ($frags as $k => $f) {
                if ($k % 2 !== 0) {
                    continue;
                }

                $assoc[$f] = $frags[$k + 1];
            }
        }

        $params['format'] = $this->detectFormat($httpRequest);
        $params[self::KEY_ASSOCIATIONS] = $assoc;
        $params['data'] = $this->readInput();
        $params[self::KEY_QUERY] = $httpRequest->getQuery();

        if ($this->useURLModuleVersioning) {
            $suffix = $presenterName;
            $presenterName = empty($this->module) ? "" : $this->module . ':';
            $presenterName .= array_key_exists($version, $this->versionToModuleMapping)
                ? $this->versionToModuleMapping[$version] . ":" . $suffix
                : $this->versionToModuleMapping[null] . ":" . $suffix;
        } else {
            $presenterName = empty($this->module) ? $presenterName : $this->module . ':' . $presenterName;
        }

        $returnArray = [
            self::KEY_PRESENTER => $presenterName,
            self::KEY_ACTION => $params['action'],
            self::KEY_METHOD => $httpRequest->getMethod(),
            self::KEY_POST => $httpRequest->getPost(),
            self::KEY_FILES => $httpRequest->getFiles(),
            'secured' => $httpRequest->isSecured()
        ];

        return array_merge($returnArray, $params);
    }

    protected function detectAction(IRequest $request): ?string
    {
        $method = $this->detectMethod($request);

        switch ($method) {
            case 'GET':
            case 'HEAD':
                return 'read';
            case 'POST':
                return 'create';
            case 'PATCH':
                return 'partialUpdate';
            case 'PUT':
                return 'update';
            case 'DELETE':
                return 'delete';
            case 'OPTIONS':
                return 'options';
            default:
                throw new InvalidStateException('Method ' . $method . ' is not allowed.');
        }
    }

    protected function detectMethod(IRequest $request): string
    {
        return $request->getMethod();
    }

    private function detectFormat(IRequest $request): string
    {
        $header = $request->getHeader('Accept'); // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
        foreach ($this->formats as $format => $fullFormatName) {
            $fullFormatName = Strings::replace($fullFormatName, '/\//', '\/');
            if ($header !== null && Strings::match($header, "/{$fullFormatName}/")) {
                return $format;
            }
        }

        // Try retrieve fallback from URL.
        $path = $request->getUrl()->getPath();
        $formats = array_keys($this->formats);
        $formats = implode('|', $formats);
        if (Strings::match($path, "/\.({$formats})$/")) {
            list($path, $format) = explode('.', $path);
            return $format;
        }

        return $this->defaultFormat;
    }

    protected function readInput(): string
    {
        return (string) file_get_contents('php://input');
    }

    /**
     * Constructs absolute URL from Request object.
     * @param array{presenter: string, method: string, id?: int|string|null, associations?: array<string, mixed>, query?: mixed[]} $params
     */
    public function constructUrl(array $params, UrlScript $refUrl): ?string
    {
        // Module prefix not match.
        if ($this->module && !Strings::startsWith($params[self::KEY_PRESENTER], $this->module)) {
            return null;
        }

        $url = $refUrl->getBaseUrl();
        $urlStack = [];

        // Module prefix.
        $moduleFrags = explode(":", $params[self::KEY_PRESENTER]);
        $moduleFrags = array_map([Inflector::class, "spinalCase"], $moduleFrags);
        $resourceName = array_pop($moduleFrags);
        $urlStack += $moduleFrags;

        // Associations.
        if (isset($params[self::KEY_ASSOCIATIONS]) && Validators::is($params[self::KEY_ASSOCIATIONS], 'array')) {
            $associations = $params[self::KEY_ASSOCIATIONS];
            unset($params[self::KEY_ASSOCIATIONS]);

            foreach ($associations as $key => $value) {
                $urlStack[] = $key;
                $urlStack[] = $value;
            }
        }

        // Resource.
        $urlStack[] = $resourceName;

        // Id.
        if (isset($params['id']) && Validators::is($params['id'], 'scalar')) {
            $urlStack[] = $params['id'];
            unset($params['id']);
        }

        $url .= implode('/', $urlStack);

        $sep = ini_get('arg_separator.input');

        if (isset($params[self::KEY_QUERY])) {
            $query = http_build_query($params[self::KEY_QUERY], '', $sep ? $sep[0] : '&');

            if ($query !== '') {
                $url .= '?' . $query;
            }
        }

        return $url;
    }
}
