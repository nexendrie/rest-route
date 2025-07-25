<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;

class FormatDetectorTest extends TestCase
{
    private function runDetectFormatMethod(RestRoute $route, Request $request): string
    {
        $method = new \ReflectionMethod($route, 'detectFormat');
        return $method->invoke($route, $request);
    }

    public function testFormatJsonWithAcceptHeader(): void
    {
        $route = new RestRoute('Api');

        $url = new UrlScript();
        $request = new Request($url, [], [], [], ['accept' => 'application/json']);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('json', $format);
    }

    public function testFormatXmlWithAcceptHeader(): void
    {
        $route = new RestRoute('Api');

        $url = new UrlScript();
        $request = new Request($url, [], [], [], ['accept' => 'application/xml']);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('xml', $format);
    }

    public function testDefaultFormatWithWildcardHeader(): void
    {
        $route = new RestRoute('Api');

        $url = new UrlScript();
        $request = new Request($url, [], [], [], ['accept' => '*/*']);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('json', $format);
    }

    public function testJsonFormatWithFallbackInUrl(): void
    {
        $route = new RestRoute('Api');

        $url = (new UrlScript())->withPath('/api/foo.json');
        $request = new Request($url);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('json', $format);
    }

    public function testXmlFormatWithFallbackInUrl(): void
    {
        $route = new RestRoute('Api');

        $url = (new UrlScript())->withPath('/api/foo.xml');
        $request = new Request($url);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('xml', $format);
    }

    public function testDefaultFormat(): void
    {
        $route = new RestRoute('Api');

        $url = (new UrlScript())->withPath('/api/foo');
        $request = new Request($url);
        $format = $this->runDetectFormatMethod($route, $request);

        $this->assertEquals('json', $format);
    }
}
