<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorAbstract;

class ItemsAddValidator extends ValidatorAbstract
{
//@TODO update dont use request
    public function check(array $payload) : bool
    {
        if (empty($payload['items'])) {
            $this->error = 'Needs at least one item';
            return false;
        }

        foreach ($payload['items'] as $name) {
            if (empty($name)) {
                $this->error = 'No name can be empty';
                return false;
            }

            if (strpos($name, 'wrong') !== false) {
                $this->error = 'No name can contain wrong';
                return false;
            }
        }

        return true;
    }
}
