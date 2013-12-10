<?php

namespace Icepay;

use Icepay\Exception\InvalidArgumentException;

class ResponseStatus {

    const OPEN = "OPEN";
    const AUTHORIZED = "AUTHORIZED";
    const ERROR = "ERR";
    const SUCCESS = "OK";
    const REFUND = "REFUND";
    const CHARGEBACK = "CBACK";

    protected $status;

    public static function getStatuses()
    {
        $refl = new \ReflectionClass('Icepay\ResponseStatus');
        return $refl->getConstants();
    }

    public function __construct($status)
    {
        if (!in_array($status, self::getStatuses())) {
            throw new InvalidArgumentException('The status "' . $status . '" is not recognized');
        }
        $this->status = $status;
    }

    public function __toString()
    {
        return $this->status;
    }
}
 