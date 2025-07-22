<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;

class ConstructUrlTest extends TestCase
{
    public function testNoModuleNoAssociations(): void
    {
        $route = new RestRoute();
        $params = [
            RestRoute::KEY_PRESENTER => 'Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987
        ];

        $refUrl = new UrlScript('http://localhost/');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://localhost/resource/987';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testWithModuleNoAssociations(): void
    {
        $route = new RestRoute('Api');
        $params = [
            RestRoute::KEY_PRESENTER => 'Api:Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987,
        ];

        $refUrl = new UrlScript('http://localhost/');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://localhost/api/resource/987';
        $this->assertEquals($expectedUrl, $url);
    }

    /**
     * @return array<int, array{associations: array<string, mixed>, result: string}>
     */
    public function createAssociations(): array
    {
        return [
            [
                'associations' => [
                    'foos' => 123,
                ],
                'result' => '/foos/123',
            ],
            [
                'associations' => [
                    'foos' => 123,
                    'bars' => 234,
                ],
                'result' => '/foos/123/bars/234',
            ],
            [
                'associations' => [
                    'foos' => 123,
                    'bars' => 234,
                    'beers' => 345,
                ],
                'result' => '/foos/123/bars/234/beers/345',
            ],
            [
                'associations' => [
                    'foos-bars' => 123,
                ],
                'result' => '/foos-bars/123',
            ],
        ];
    }

    /**
     * @dataProvider createAssociations
     * @param array<string, mixed> $associations
     */
    public function testWithModuleAndAssociations(array $associations, string $result): void
    {
        $route = new RestRoute('Api');
        $params = [
            RestRoute::KEY_PRESENTER => 'Api:Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987,
            RestRoute::KEY_ASSOCIATIONS => $associations
        ];

        $refUrl = new UrlScript('http://localhost/');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = "http://localhost/api{$result}/resource/987";
        $this->assertEquals($expectedUrl, $url);
    }

    public function testDefaultsWithBasePath(): void
    {
        $route = new RestRoute();
        $params = [
            RestRoute::KEY_PRESENTER => 'Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987,
        ];

        $refUrl = (new UrlScript('http://localhost/base-path/'));
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://localhost/base-path/resource/987';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testUrlOnSubdomain(): void
    {
        $route = new RestRoute();
        $params = [
            RestRoute::KEY_PRESENTER => 'Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987,
        ];

        $refUrl = new UrlScript('http://api.foo.bar');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://api.foo.bar/resource/987';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testQueryParams(): void
    {
        $route = new RestRoute();
        $params = [
            RestRoute::KEY_PRESENTER => 'Resource',
            RestRoute::KEY_METHOD => \Nette\Http\Request::GET,
            'id' => 987,
            'query' => [
                'foo' => 'bar',
                'baz' => 'bay',
            ],
        ];

        $refUrl = new UrlScript('http://api.foo.bar');
        $url = $route->constructUrl($params, $refUrl);

        $expectedUrl = 'http://api.foo.bar/resource/987?foo=bar&baz=bay';
        $this->assertEquals($expectedUrl, $url);
    }
}
