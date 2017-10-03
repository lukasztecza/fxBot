<?php
namespace TinyApp\Model\Middleware;

class RenderingMiddleware
{

public function __construct($con, $act)
{
$this->con = $con;
$this->act = $act;

}

}
