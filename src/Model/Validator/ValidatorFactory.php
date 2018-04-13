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

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
        $this->csrfToken = $this->generateCsrfToken();
    }

    public function create(string $class) : ValidatorInterface
    {
        $classInterfaces = class_implements($class);
        if (
            !in_array(ArrayValidatorInterface::class, $classInterfaces) &&
            !in_array(RequestValidatorInterface::class, $classInterfaces)
        ) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . ArrayValidatorInterface::class . ' or ' . RequestValidatorInterface::class);
        }
        
        //@TODO pass instance if exists and do not create new one each time
        // do not pass csrfToken to ArrayValidators
        return new $class($this->csrfToken);
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
