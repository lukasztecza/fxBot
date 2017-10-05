<?php
namespace TinyApp\Model\Middleware;

use TinyApp\System\Request;

interface ApplicationMiddlewareInterface
{
    public function process(Request $request);
}
