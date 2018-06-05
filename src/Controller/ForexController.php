<?php
namespace FxBot\Controller;

use LightApp\Controller\ControllerAbstract;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;

class ForexController extends ControllerAbstract
{
    public function home(Request $request) : Response
    {
        return $this->htmlResponse('home.php');
    }

    public function stats(Request $request) : Response
    {
        return $this->htmlResponse('stats.php');
    }

}
