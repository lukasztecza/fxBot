<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorAbstract;
use TinyApp\Model\System\Request;

class ItemsDeleteValidator extends ValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['ids']);

        if (empty($payload['ids'])) {
            $this->error = 'Needs at least one id';

            return false;
        }

        foreach ($payload['ids'] as $id) {
            if (!is_numeric($id)) {
                $this->error = 'Only numbers are allowed';

                return false;
            }
        }

        return true;
    }
}
