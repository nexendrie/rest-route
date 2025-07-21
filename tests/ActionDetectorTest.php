<?php
declare(strict_types=1);

namespace AdamStipak;

use Nette\Http\UrlScript;
use Nette\Http\Request;
use PHPUnit\Framework\TestCase;

class ActionDetectorTest extends TestCase
{
    /**
     * @param string $method
     * @param string $action
     *
     * @dataProvider getActions
     */
    public function testAction(string $method, string $action): void
    {
        $route = new RestRoute();

        $url = (new UrlScript())->withPath('/foo');
        $request = new Request($url, [], [], [], [], $method);

        $parameters = $route->match($request);

        $this->assertEquals('Foo', $parameters[RestRoute::KEY_PRESENTER]);
        $this->assertEquals($action, $parameters[RestRoute::KEY_ACTION]);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public function getActions(): array
    {
        return [
            ['POST', 'create'],
            ['GET', 'readAll'],
            ['HEAD', 'readAll'],
            ['PATCH', 'partialUpdate'],
            ['PUT', 'update'],
            ['DELETE', 'delete'],
            ['OPTIONS', 'options'],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public function getActionsForOverride(): array
    {
        return [
            ['PATCH', 'partialUpdate'],
            ['PUT', 'update'],
            ['DELETE', 'delete'],
        ];
    }
}
