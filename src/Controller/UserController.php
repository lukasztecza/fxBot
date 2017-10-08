<?php
namespace TinyApp\Controller;

use TinyApp\System\Request;
use TinyApp\System\Response;

class UserController
{
    private $txt;
    private $num;
    private $userService;

    public function __construct($txt, $num, $userService)
    {
        $this->txt = $txt;
        $this->num = $num;
        $this->userService = $userService;
    }

    public function get(Request $request)
    {
//        list($ss, $post1) = array_values($request->getPayload(['post3.subp2.ss', 'post1']));
        $this->userService->something();
        return new Response(
            'layout.php',
//            null,
            ['first' => 123, 'second' => '<div>hey</div>', 'third' => ['hey' => [10,30,50,'test'], 'wow']],
            ['third.0' => 'raw', 'first' => 'html', 'second' => 'raw', 'third.hey.1' => 'raw', 'template' => 'raw'],
//            ['Content-Type' => 'application/json'],
            []
        );
    }
}
