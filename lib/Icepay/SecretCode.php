<?php
namespace Icepay;

use Icepay\Exception\InvalidArgumentException;

class SecretCode {
    protected $secret;

    public function __construct($secret)
    {
        if (strlen($secret) != 40 || is_numeric($secret)) {
            throw new InvalidArgumentException('Secret codes should be 40 chars long and should not only contain digits, "' . $secret . '" does not meet these specifications');
        }
        $this->secret = $secret;
    }

    public function __toString()
    {
        return (string)$this->secret;
    }
}
 