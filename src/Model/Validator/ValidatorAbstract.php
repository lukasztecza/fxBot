<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;

abstract class ValidatorAbstract implements ValidatorInterface
{
    protected $error = '';

    public function getError() : string
    {
        return $this->error;
    }

    abstract public function check(array $payload) : bool;
}
