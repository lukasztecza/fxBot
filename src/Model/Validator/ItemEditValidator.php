<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\System\Request;
use TinyApp\Model\Validator\ValidatorAbstract;

class ItemEditValidator extends ValidatorAbstract
{
    public function check(array $payload) : bool
    {
        if (empty($payload['name'])) {
            $this->error = 'name can not be empty';
            return false;
        }

        if (strpos($payload['name'], 'wrong') !== false) {
            $this->error = 'name can not contain wrong';
            return false;
        }

        return true;
    }
}
