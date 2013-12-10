<?php
namespace Icepay;

use Icepay\Exception\InvalidArgumentException;

class MerchantID {
    protected $id;

    public function __construct($id)
    {
        if (strlen($id) != 5 || !is_numeric($id)) {
            throw new InvalidArgumentException('MerchantIDs should be 5 digit long numbers, "' . $id . '" does not satisfy these conditions');
        }
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
 