<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;
use TinyApp\Model\Validator\ValidatorAbstract;

class LoginValidator extends ValidatorAbstract
{
    public function check(array $payload) : bool
    {
        if (empty($payload['username']) || empty($payload['password'])) {
            $this->error = 'Fields username and password can not be empty';
            return false;
        }

        return true;
    }
}
