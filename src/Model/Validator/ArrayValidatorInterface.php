<?php
namespace TinyApp\Model\Validator;

interface ArrayValidatorInterface
{
    public function check(array $values) : bool;
}
