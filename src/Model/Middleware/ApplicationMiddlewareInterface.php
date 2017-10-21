<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

interface ApplicationMiddlewareInterface
{
    public function process(Request $request) : Response;
}
