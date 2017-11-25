<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorAbstract;
use TinyApp\Model\System\Request;

class FilesUploadValidator extends ValidatorAbstract
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
