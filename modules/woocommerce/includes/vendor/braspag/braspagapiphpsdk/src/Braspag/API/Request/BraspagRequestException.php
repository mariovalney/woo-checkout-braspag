<?php
namespace Braspag\API\Request;

class BraspagRequestException extends \Exception
{

    private $cieloError;

    public function __construct($message, $code, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getBraspagError()
    {
        return $this->cieloError;
    }

    public function setBraspagError(BraspagError $cieloError)
    {
        $this->cieloError = $cieloError;
        return $this;
    }
}