<?php
namespace TinyApp\Model\Validator;

use TinyApp\Model\Validator\ValidatorInterface;
use TinyApp\Model\System\Request;

abstract class ValidatorAbstract implements ValidatorInterface
{
    protected $error = '';
    private $csrfToken;

    public function __construct(string $csrfToken)
    {
        $this->csrfToken = $csrfToken;
    }

    public function getError() : string
    {
        return $this->error;
    }

    final public function check(Request $request, $checkOrigin = true, $checkCsrfToken = true) : bool
    {
        extract($request->getPayload(['csrfToken']));
       
        if ($checkOrigin) {
            switch (true) {
                case !empty($request->getServer()['HTTP_ORIGIN']):
                    $httpOrigin = $request->getServer()['HTTP_ORIGIN'];
                    break;
                case !empty($request->getServer()['HTTP_REFERER']):
                    $httpReferer = $request->getServer()['HTTP_REFERER'];
                    $length = strpos(str_replace('//', '', $httpReferer), '/');
                    $length = $length > 0 ? $length + 2 : strlen($httpReferer);
                    $httpOrigin = substr($httpReferer, 0, $length);
                    break;
                default:
                    $httpOrigin = null;
                    break;
            }

            if ($request->getHost() !== $httpOrigin) {
                $this->error = 'Origin is not valid';
                return false;
            }
        }

        if ($checkCsrfToken && $csrfToken !== $this->csrfToken) {
            $this->error = 'Csrf token not valid';
            return false;
        }

        return $this->validate($request);
    }

    public function getCsrfToken() : string
    {
        return $this->csrfToken;
    }

    abstract protected function validate(Request $request) : bool;
}
