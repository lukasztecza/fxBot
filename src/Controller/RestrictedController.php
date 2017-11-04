<?php
namespace TinyApp\Controller;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class RestrictedController implements ControllerInterface
{
    public function restricted2(Request $request)
    {
        return new Response(
            'restricted.php',
            ['restrictedValue' => '<p style="color:red">This is restrictd site '. 'restricted2' .'</p>'],
            ['restrictedValue' => 'raw']
        );
    }

    public function restricted(Request $request)
    {
        return new Response(
            'restricted.php',
            ['restrictedValue' => '<p style="color:red">This is restrictd site '. $request->getAttributes(['code'])['code'] .'</p>'],
            ['restrictedValue' => 'raw']
        );
    }
}
