<?php
namespace TinyApp\Controller;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class SampleController
{
    private $someText;
    private $sampleService;

    public function __construct($someText, $sampleService)
    {
        $this->someText = $someText;
        $this->sampleService = $sampleService;
    }

    public function home(Request $request) : Response
    {
        $items = $this->sampleService->getItems();

        return new Response(
            'layout.php',
            ['items' => $items, 'message' => '<h3>Welcome</h3>'],
            ['message' => 'raw']
        );
    }

    public function get(Request $request) : Response
    {
        list($id) = array_values($request->getAttributes(['id']));
        $myItem = $this->sampleService->getItem($id);
        return new Response(
            'layout.php',
            ['items' => [$myItem]]
        );
    }

    public function save(Request $request) : Response
    {
        list($blah) = array_values($request->getPayload(['blah']));
        $key = $this->sampleService->saveItems(['extra item 1', 'extra item 2']);

        return new Response(
            null,
            [],
            [],
            ['Location' => '/item/' . $key]
        );
    }

    public function api()
    {
        return new Response(
            null,
            ['api_value' => 123],
            [],
            ['Content-Type' => 'application/json']
        );
    }

}
