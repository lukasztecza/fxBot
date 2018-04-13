<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;
use TinyApp\Model\Validator\ArrayValidatorInterface;
use TinyApp\Model\Validator\RequestValidatorInterface;
use TinyApp\Model\Service\SessionService;

class ValidatorFactory
{
    private $sessionService;
    private $csrfToken;
    private $validators;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
        $this->csrfToken = $this->generateCsrfToken();
        $this->validators = [];
    }

    public function create(string $class) : ValidatorInterface
    {
        $classInterfaces = class_implements($class);
        $requestValidator = in_array(RequestValidatorInterface::class, $classInterfaces);
        if (
            !in_array(ArrayValidatorInterface::class, $classInterfaces) &&
            !$requestValidator
        ) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . ArrayValidatorInterface::class . ' or ' . RequestValidatorInterface::class);
        }

        if (!isset($this->validators[$class]) {
            if ($requestValidator) {
                $this->validators[$class] = new $class($this->csrfToken);
            }
            $this->validators[$class] = new $class();
        }

        return $this->validators[$class];
    }

    private function generateCsrfToken() : string
    {
        $csrfToken = $this->sessionService->get(['csrfToken'])['csrfToken'];
        if ($csrfToken !== null) {
            return $csrfToken;
        }
//@TODO use bin2hex and random_byte
        $value = md5(time() . random_int(1,1000000));
        $this->sessionService->set(['csrfToken' => $value]);

        return $value;
    }
}
