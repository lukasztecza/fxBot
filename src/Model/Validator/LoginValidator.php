<?php
namespace FxBot\Model\Validator;

use LightApp\Model\Validator\RequestValidatorAbstract;
use LightApp\Model\System\Request;

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
