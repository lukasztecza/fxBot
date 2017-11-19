<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorAbstract;
use TinyApp\Model\System\Request;

class ItemsDeleteValidator extends ValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $ids = $request->getPayload(['ids'])['ids'];

        if (empty($ids)) {
            $this->error = 'Needs at least one id';

            return false;
        }

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                $this->error = 'Only numbers are allowed';

                return false;
            }
        }

        return true;
    }
}
