<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class ForexController implements ControllerInterface
{
    public function home(Request $request) : Response
    {
        return new Response('home.php');
    }
}
