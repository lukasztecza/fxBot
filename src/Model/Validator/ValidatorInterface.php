<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;

interface ValidatorInterface
{
    public function getError() : string;
    public function check(Request $request) : bool;
}
