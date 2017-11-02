<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorAbstract;
use TinyApp\Model\System\Request;

class ItemsAddValidator extends ValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['items']);

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
