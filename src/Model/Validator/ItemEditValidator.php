<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;
use TinyApp\Model\Validator\ValidatorAbstract;

class ItemEditValidator extends ValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['name']);
        if (empty($paylaod)) {
            $payload = $request->getInput(['name']);
        }

        if (empty($payload['name'])) {
            $this->error = 'Value of name can not be empty';

            return false;
        }

        if (strpos($payload['name'], 'wrong') !== false) {
            $this->error = 'Value of name can not contain wrong';

            return false;
        }

        return true;
    }
}
