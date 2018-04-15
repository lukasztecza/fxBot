<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;
use TinyApp\Model\Validator\ValidatorAbstract;
use TinyApp\Model\Validator\ArrayValidatorInterface;

abstract class ArrayValidatorAbstract extends ValidatorAbstract implements ArrayValidatorInterface
{
    abstract public function check(array $values) : bool
}
