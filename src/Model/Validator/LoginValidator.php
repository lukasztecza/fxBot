<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;
use TinyApp\Model\Validator\RequestValidatorAbstract;

class LoginValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['username', 'password']);
        if (empty($payload['username']) || empty($payload['password'])) {
            $this->error = 'Fields username and password can not be empty';

            return false;
        }

        return true;
    }
}
