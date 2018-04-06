<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;

interface RequestValidatorInterface
{
    public function check(Request $request) : bool;
}
