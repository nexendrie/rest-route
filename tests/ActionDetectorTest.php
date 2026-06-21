<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

use MyTester\Attributes\DataProvider;
use MyTester\TestCase;
use Nette\Http\UrlScript;
use Nette\Http\Request;

class ActionDetectorTest extends TestCase
{
    /**
     * @param string $method
     * @param string $action
     */
    #[DataProvider("getActions")]
    public function testAction(string $method, string $action): void
    {
        $route = new RestRoute();

        $url = (new UrlScript())->withPath('/foo');
        $request = new Request($url, [], [], [], [], $method);

        $parameters = $route->match($request);

        $this->assertSame('Foo', $parameters[RestRoute::KEY_PRESENTER]); // @phpstan-ignore offsetAccess.notFound
        $this->assertSame($action, $parameters[RestRoute::KEY_ACTION]); // @phpstan-ignore offsetAccess.notFound
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function getActions(): array
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
