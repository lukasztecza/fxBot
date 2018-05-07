<?php
use TinyApp\Model\System\Router;
use Codeception\Example;

class RouterCest
{
    public $router;

    private function callNonPublic(object $object, string $method, array $params)
    {
        return (function () use ($object, $method, $params) {
            return call_user_func_array([$object, $method], $params);
        })->bindTo($object, $object)();
    }

    public function _before()
    {
        $this->router = new Router([
            0 => [
                'path'=> '/items/{id}',
                'methods'=> ['GET'],
                'requirements'=> ['id' => '\d+'],
                'controller'=> 'itemsController',
                'action'=> 'details'
            ],
            1 => [
                'path'=> '/main',
                'methods'=> ['GET'],
                'controller'=> 'mainController',
                'action'=> 'index'
            ],
            2 => [
                'path'=> '/items/{id}/group/{name}',
                'methods'=> ['POST'],
                'requirements'=> ['id' => '\d+', 'name' => '[a-z]{4}'],
                'controller'=> 'itemsController',
                'action'=> 'modify'
            ]
        ]);
    }

    public function _after()
    {
    }

    /**
     * @dataProvider keyPathDataProvider
     */
    public function getRouteAttributesSuccess(UnitTester $I, Example $example)
    {
        $result = $this->callNonPublic($this->router, 'getRouteAttributes', [$example[0], $example[1]]);
        $I->assertEquals($example[2], $result);
    }

    public function keyPathDataProvider()
    {
        return [
            [0, '/items/5', ['id' => 5]],
            [1, '/main', []],
            [2, '/items/5/group/list', ['id' => 5, 'name' => 'list']]
        ];
    }

    /**
     * @dataProvider pathMethodDataProvider
     */
    public function getMatchingRouteSuccess(UnitTester $I, Example $example)
    {
        $result = $this->callNonPublic($this->router, 'getMatchingRoute', [$example[0], $example[1]]);
        $I->assertEquals($example[2], $result);
    }

    public function pathMethodDataProvider()
    {
        return [
            ['/items/5', 'GET', 0],
            ['/main', 'GET', 1],
            ['/items/5/group/list', 'POST', 2]
        ];
    }

    /**
     * @dataProvider wrongPathMethodDataProvider
     */
    public function getMatchingRouteFail(UnitTester $I, Example $example)
    {
        $I->expectException(
            new Exception('No route found for path ' . var_export($example[0], true), 404),
            function () use ($example) {
                $this->callNonPublic($this->router, 'getMatchingRoute', [$example[0], $example[1]]);
            }
        );
    }

    public function wrongPathMethodDataProvider()
    {
        return [
            ['/items/wrong', 'GET'],
            ['/wrong', 'GET'],
            ['/items/5/group/wrong', 'POST']
        ];
    }
}
