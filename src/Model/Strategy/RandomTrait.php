<?php
namespace TinyApp\Model\Strategy;

trait RandomTrait
{
    protected function getDirection(string $currentDateTime = null, string $selectedInstrument = null) : int
    {
        $direction = rand(0, 1);
        return $direction === 1 ? 1 : -1;
    }
}
