<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;
use TinyApp\Model\Validator\ArrayValidatorInterface;

abstract class ArrayValidatorAbstract implements ValidatorInterface, ArrayValidatorInterface
{
    protected $error = '';

    public function getError() : string
    {
        return $this->error;
    }

    abstract public function check(array $values) : bool
}
