<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;

interface ApplicationMiddlewareInterface
{
    public function process(Request $request);
}
