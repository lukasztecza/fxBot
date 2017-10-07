<?php
namespace TinyApp\System;

use TinyApp\System\Request;

interface ApplicationMiddlewareInterface
{
    public function process(Request $request);
}
