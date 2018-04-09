<?php
namespace TinyApp\Model\Validator;

interface ValidatorInterface
{
    public function getError() : string;
}
