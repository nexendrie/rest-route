<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use Nette\Http\UrlScript;
use Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class RestRouteTest extends TestCase
{
    public function testConstructorWithNoModule(): void
    {
        $route = new RestRoute();
        $this->assertInstanceOf(RestRoute::class, $route);
    }

    public function testConstructorWithEmptyDefaultFormat(): void
    {
        $route = new RestRoute('Api');
        $this->assertInstanceOf(RestRoute::class, $route);
    }

    public function testConstructorWithInvalidDefaultFormat(): void
    {
        $this->expectException(\Nette\InvalidArgumentException::class);
        $route = new RestRoute('Api', 'invalid');
    }

    public function testConstructorWithXmlAsADefaultFormat(): void
    {
        $route = new RestRoute('Api', 'xml');

        $this->assertEquals('xml', $route->defaultFormat);
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
        $url = $route->constructUrl($appRequest, $refUrl);

        $expectedUrl = 'http://localhost/resource?access_token=foo-bar';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testMatchAndConstructSpinalCaseUrlSingleResource(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/re-source');

        $request = new Request($url, [], [], [], [], 'GET');

        $params = $route->match($request);
        $expectedPresenterName = 'ReSource';
        $this->assertEquals($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://localhost/re-source';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testMatchAndConstructSpinalCaseUrlMultipleResource(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/first-level/123/second-level/456/re-source', '/');

        $request = new Request($url, [], [], [], [], 'GET');

        $params = $route->match($request);
        $expectedPresenterName = 'ReSource';
        $this->assertEquals($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://localhost/first-level/123/second-level/456/re-source';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testFileUpload(): void
    {
        $route = new RestRoute();

        $url = (new UrlScript('http://localhost'))->withPath('/whatever');
        $files = ['file1', 'file2', 'file3'];

        $request = new Request($url, [], $files, [], [], 'POST');
        $params = $route->match($request);

        $this->assertEquals($files, $params[RestRoute::KEY_FILES]);
    }

    /**
     * @dataProvider getActions
     * @param mixed[]|null $associations
     */
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

        $this->assertEquals('Foo', $params[RestRoute::KEY_PRESENTER]);
        $this->assertEquals($action, $params[RestRoute::KEY_ACTION]);

        if ($id) {
            $this->assertEquals($id, $params['id']); // @phpstan-ignore offsetAccess.notFound
        }
        if ($associations) {
            $this->assertSame($associations, $params[RestRoute::KEY_ASSOCIATIONS]);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3?: int}>
     */
    public static function getActions(): array
    {
        return [
            ['POST', '/foo', 'create'],
            ['GET', '/foo', 'readAll'],
            ['GET', '/foo/1', 'read', 1],
            ['HEAD', '/foo', 'readAll'],
            ['HEAD', '/foo/1', 'read', 1],
            ['PATCH', '/foo', 'partialUpdate'],
            ['PUT', '/foo', 'update'],
            ['DELETE', '/foo', 'delete'],
            ['OPTIONS', '/foo', 'options'],
        ];
    }

    /**
     * @dataProvider getVersions
     */
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

        $this->assertEquals($expectedPresenterName, $params[RestRoute::KEY_PRESENTER]);

        $refUrl = new UrlScript('http://localhost');
        $url = $route->constructUrl($params, $refUrl);
        $this->assertEquals($expectedUrl, $url);
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
