<?php
namespace TinyApp\Model\Strategy;

use TinyApp\Model\Validator\ValidatorInterface;
use TinyApp\Model\Service\SessionService;

class StrategyFactory
{
    public function getStrategy(string $class) : StrategyInterface
    {
        if (!in_array(StrategyInterface::class, class_implements($class))) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . StrategyInterface::class);
        }

        return new $class();
    }
}
