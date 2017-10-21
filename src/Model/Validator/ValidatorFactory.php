<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;

class ValidatorFactory
{
    public function create(string $class) : ValidatorInterface
    {
        if (!in_array(ValidatorInterface::class, class_implements($class))) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . ValidatorInterface::class);
        }

        return new $class();
    }
}
