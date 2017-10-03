<?php
namespace TinyApp\Controller;

class UserController
{
    public function __construct($txt, $num, $srv) 
    {
        $this->txt = $txt;
        $this->num = $num;
        $this->srv = $srv;
    }

    public function get()
    {
        list($ss, $post1) = array_values($request->getPayload(['post3.subp2.ss', 'post1']));
    }
}
