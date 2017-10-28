<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;
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
        if (!in_array(ValidatorInterface::class, class_implements($class))) {
            throw new \Exception('Wrong class exception, ' . $class . ' has to implement ' . ValidatorInterface::class);
        }

        return new $class($this->csrfToken);
    }

    private function generateCsrfToken() : string
    {
        extract($this->sessionService->get(['csrfToken']));
        if ($csrfToken !== null) {
            return $csrfToken;
        }

        $value = md5(time() . rand(1,1000000));
        $this->sessionService->set(['csrfToken' => $value]);

        return $value;
    }
}
