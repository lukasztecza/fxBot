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
//        list($ss, $post1) = array_values($request->post(['post3.subp2.ss', 'post1']));
        $this->userService->something();
        return new Response(['first' => 123, 'second' => 'hey', 'template' => 'layout.php'], ['Content-Type' => 'pplication/json']);
    }
}
