<?php declare(strict_types=1);
namespace FxBot\Model\Validator;

use LightApp\Model\Validator\RequestValidatorAbstract;
use LightApp\Model\System\Request;

class CompareValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['type', 'instrument']);
        if (empty($payload['type']) || empty($payload['instrument'])) {
            $this->error = 'Fields type and instrument can not be empty';

            return false;
        }

        if (!in_array($payload['type'], $this->params['validTypes'])) {
            $this->error = 'Field type has to be one of values: ' . implode(', ', $this->params['validTypes']);

            return false;
        }

        if (!in_array($payload['instrument'], $this->params['validInstruments'])) {
            $this->error = 'Field instrument has to be one of values: ' . implode(', ', $this->params['validInstruments']);

            return false;
        }

        return true;
    }
}
