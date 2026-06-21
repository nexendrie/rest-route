<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use MyTester\Attributes\DataProvider;
use MyTester\TestCase;
use Nette\Http\UrlScript;
use Nette\Http\Request;

class RestRouteTest extends TestCase
{
    public function testConstructorWithNoModule(): void
    {
        $route = new RestRoute();
        $this->assertType(RestRoute::class, $route);
    }

    public function testConstructorWithEmptyDefaultFormat(): void
    {
        $route = new RestRoute('Api');
        $this->assertType(RestRoute::class, $route);
    }

    public function testConstructorWithInvalidDefaultFormat(): void
    {
        $this->assertThrowsException(static function () {
            $route = new RestRoute('Api', 'invalid');
        }, InvalidArgumentException::class);
    }

    public function testConstructorWithXmlAsADefaultFormat(): void
    {
        $route = new RestRoute('Api', 'xml');

        $this->assertSame('xml', $route->defaultFormat);
    }

    public function testMatchAndConstructUrl(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))
            ->withPath('/resource')
            ->withQuery(['access_token' => 'foo-bar']);

        $request = new Request($url, [], [], [], [], 'GET');

        $appRequest = $route->match($request);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($appRequest, $refUrl); // @phpstan-ignore argument.type

        $expectedUrl = 'http://localhost/resource?access_token=foo-bar';
        $this->assertSame($expectedUrl, $url);
    }

    public function testMatchAndConstructSpinalCaseUrlSingleResource(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/re-source');

        $request = new Request($url, [], [], [], [], 'GET');

        $params = $route->match($request);
        $expectedPresenterName = 'ReSource';
        // @phpstan-ignore offsetAccess.notFound
        $this->assertSame($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl); // @phpstan-ignore argument.type

        $expectedUrl = 'http://localhost/re-source';
        $this->assertSame($expectedUrl, $url);
    }

    public function testMatchAndConstructSpinalCaseUrlMultipleResource(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/first-level/123/second-level/456/re-source', '/');

        $request = new Request($url, [], [], [], [], 'GET');

        $params = $route->match($request);
        $expectedPresenterName = 'ReSource';
        // @phpstan-ignore offsetAccess.notFound
        $this->assertSame($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl); // @phpstan-ignore argument.type

        $expectedUrl = 'http://localhost/first-level/123/second-level/456/re-source';
        $this->assertSame($expectedUrl, $url);
    }

    public function testFileUpload(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/whatever');
        $files = ['file1', 'file2', 'file3'];

        $request = new Request($url, [], $files, [], [], 'POST');
        $params = $route->match($request);

        $this->assertSame($files, $params[RestRoute::KEY_FILES]); // @phpstan-ignore offsetAccess.notFound
    }

    /**
     * @param mixed[]|null $associations
     */
    #[DataProvider("getActions")]
    public function testDefault(
        string $method,
        string $path,
        string $action,
        ?int $id = null,
        ?array $associations = null
    ): void {
        $route = new RestRoute();

        $url = (new UrlScript())->withPath($path, '/');
        $request = new Request($url, [], [], [], [], $method);

        $params = $route->match($request);

        $this->assertSame('Foo', $params[RestRoute::KEY_PRESENTER]); // @phpstan-ignore offsetAccess.notFound
        $this->assertSame($action, $params[RestRoute::KEY_ACTION]); // @phpstan-ignore offsetAccess.notFound

        if ($id !== null) {
            $this->assertSame((string) $id, $params['id']); // @phpstan-ignore offsetAccess.notFound
        }
        if ($associations !== null) {
            // @phpstan-ignore offsetAccess.notFound
            $this->assertSame($associations, $params[RestRoute::KEY_ASSOCIATIONS]);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: int|null, 4: null}>
     */
    public static function getActions(): array
    {
        return [
            ['POST', '/foo', 'create', null, null,],
            ['GET', '/foo', 'readAll', null, null,],
            ['GET', '/foo/1', 'read', 1, null,],
            ['HEAD', '/foo', 'readAll', null, null,],
            ['HEAD', '/foo/1', 'read', 1, null,],
            ['PATCH', '/foo', 'partialUpdate', null, null,],
            ['PUT', '/foo', 'update', null, null,],
            ['DELETE', '/foo', 'delete', null, null,],
            ['OPTIONS', '/foo', 'options', null, null,],
        ];
    }

    #[DataProvider("getVersions")]
    public function testModuleVersioning(
        ?string $module,
        string $path,
        string $expectedPresenterName,
        string $expectedUrl
    ): void {
        $route = new RestRoute($module);
        $route->useURLModuleVersioning(
            RestRoute::MODULE_VERSION_PATH_PREFIX_PATTERN,
            [
                null => 'V1',
                'v1' => 'V1',
                'v2' => 'V2'
            ]
        );

        $url = (new UrlScript())->withPath($path, '/');
        $request = new Request($url, [], [], [], [], 'GET');

        $params = $route->match($request);

        // @phpstan-ignore offsetAccess.notFound
        $this->assertSame($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl); // @phpstan-ignore argument.type
        $this->assertSame($expectedUrl, $url);
    }

    /**
     * @return array<int, array{0: string|null, 1: string, 2: string, 3: string}>
     */
    public static function getVersions(): array
    {
        return [
            [null, '/foo', 'V1:Foo', 'http://localhost/v1/foo'],
            [null, '/v1/foo', 'V1:Foo', 'http://localhost/v1/foo'],
            [null, '/v2/foo', 'V2:Foo', 'http://localhost/v2/foo'],
            ['Api', '/api/foo', 'Api:V1:Foo', 'http://localhost/api/v1/foo'],
            ['Api', '/api/v1/foo', 'Api:V1:Foo', 'http://localhost/api/v1/foo'],
        ];
    }
}
