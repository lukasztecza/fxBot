<?php
namespace TinyApp\Model\Service;

class UserService
{
    private $userRepository;

    public function __construct($userRepository) {
        $this->userRepository = $userRepository;
    }

    public function something()
    {
        $this->userRepository->getUser();
    }
}
