<?php
use TinyApp\Model\System\Request;
use Codeception\Example;

class RequestCest
{
    public $request;

    private function callNonPublic($object, string $method, array $params)
    {
        return (function () use ($object, $method, $params) {
            return call_user_func_array([$object, $method], $params);
        })->bindTo($object, $object)();
    }

    public function _before()
    {
        $this->request = new Request(
            'localhost',
            '/path/123',
            '/path/{routeAttribute}',
            ['routeAttribute' => 123],
            'GET',
            ['queryParam' => '456'],
            ['formParam' => '789'],
            [
                'singleFile' => [
                    'name' => 'fileName1',
                    'tmp_name' => 'abc123'
                ],
                'fileBox' => [
                    'name' => [
                        'fileName2', 'fileName3'
                    ],
                    'tmp_name' => [
                        'abc456', 'abc789'
                    ]
                ]
            ],
            '{"jsonParam":"987"}',
            ['someCookie' => 654],
            ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest', 'SERVER_PROTOCOL' => 'HTTP 1.0'],
            'someController',
            'someAction'
        );
    }

    public function _after()
    {
    }

    /**
     * @dataProvider combinedKeysArrayToFilterProvider
     */
    public function getFromArrayTest(UnitTester $I, Example $example)
    {
        $result = $this->callNonPublic($this->request, 'getFromArray', [$example[0], $example[1]]);
        $I->assertEquals($example[2], $result);
    }

    private function combinedKeysArrayToFilterProvider()
    {
        $arrayToFilter = [
            'first' => 123,
            'second' => [
                456, 'something', 789
            ],
            'third' => [
                'nested' => [
                    'deep' => 'secret'
                ]
            ]

        ];
        return [
            [['unexisting'], $arrayToFilter, ['unexisting' => null]],
            [['first'], $arrayToFilter, ['first' => 123]],
            [['first.unexisting'], $arrayToFilter, ['first.unexisting' => null]],
            [['second'], $arrayToFilter, ['second' => $arrayToFilter['second']]],
            [['third.nested.deep'], $arrayToFilter, ['third.nested.deep' => 'secret']],
        ];
    }

    public function getHostTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getHost(), 'localhost');
    }

    public function getPathTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getPath(), '/path/123');
    }

    public function getRouteTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getRoute(), '/path/{routeAttribute}');
    }

    public function getAttributesTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getAttributes(['routeAttribute', 'unexisting']), ['routeAttribute' => 123, 'unexisting' => null]);
    }

    public function getMethodTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getMethod(), 'GET');
    }

    public function isAjaxTest(UnitTester $I)
    {
        $I->assertEquals($this->request->isAjax(), true);
    }

    public function getQueryTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getQuery(), ['queryParam' => '456']);
    }

    public function getPayloadTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getPayload(), ['formParam' => '789']);
    }

    public function getFilesTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getFiles(), [
            'singleFile' => [
                'name' => 'fileName1',
                'tmp_name' => 'abc123'
            ],
            'fileBox' => [
                [
                    'name' => 'fileName2',
                    'tmp_name' => 'abc456'
                ],
                [
                    'name' => 'fileName3',
                    'tmp_name' => 'abc789'
                ]
            ]
        ]);
    }

    public function getInputTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getInput([], 'json'), ['jsonParam' => '987']);
    }

    public function getCookiesTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getCookies(), ['someCookie' => '654']);
    }

    public function getServerTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getServer(['SERVER_PROTOCOL']), ['SERVER_PROTOCOL' => 'HTTP 1.0']);
    }

    public function getControllerTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getController(), 'someController');
    }

    public function getActionTest(UnitTester $I)
    {
        $I->assertEquals($this->request->getAction(), 'someAction');
    }
}
