<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\RequestValidatorAbstract;
use TinyApp\Model\System\Request;

class FilesUploadValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $files = $request->getFiles();
        if (empty($files['someFile']['name'])) {
            $this->error = 'Needs at least one file';

            return false;
        }

        return true;
    }
}
