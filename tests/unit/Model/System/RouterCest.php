<?php
use TinyApp\Model\System\Router;

class RouterCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    public function tryToTest(UnitTester $I)
    {
        $router = new Router([
            0 => [
                'path'=> '/itmes/{id}',
                'methods'=> ['GET'],
                'requirements'=> ['id'=> '\d+'],
                'controller'=> 'itemsController',
                'action'=> 'details'
            ]
        ]);

        $result = (function ($method, $param1, $param2) {return $this->$method($param1,$param2);})
            ->bindTo($router, $router)('getRouteAttributes', 0,'/items/5')
        ;

        $I->assertEquals(['id' => 5], $result);
    }
}
