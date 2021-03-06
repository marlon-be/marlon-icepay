<?php
namespace Icepay;

use Icepay\Exception\BadMethodCallException;
use Icepay\Exception\InvalidArgumentException;
use Icepay\Exception\MissingParameterException;
use Icepay\Exception\UnsupportedException;

class PaymentRequest
{
    /** @var array allowed PaymentMethods */
    protected $allowedMethods;

    /** @var array */
    protected $parameters;

    protected $requiredParameters = array(
        'language', 'amount', 'currency', 'orderID'  // paymentMethod omitted because the user will be prompted for it in basic mode
    );

    public function __construct($methods = array())
    {
        $this->parameters = array();
        $this->allowedMethods = $methods;
    }

    public function setCountry($country)
    {
        if (strlen($country) != 2) {
            throw new InvalidArgumentException('The country code should be exactly 2 chars long');
        }
        $this->parameters['country'] = $country;
    }

    public function setLanguage($language)
    {
        if (strlen($language) != 2 || is_numeric($language)) {
            throw new InvalidArgumentException('The language should be a 2 char long, non-numeric string');
        }
        $this->parameters['language'] = $language;
    }

    /**
     * Set amount in cents, eg EUR 12.34 is written as 1234
     */
    public function setAmount($amount)
    {
        if(!is_int($amount)) {
            throw new InvalidArgumentException("Integer expected. Amount is always in cents");
        }
        $this->parameters['amount'] = $amount;
    }

    public function setCurrency($currency)
    {
        if (strlen($currency) != 3 || is_numeric($currency)) {
            throw new InvalidArgumentException('The currency should be a 3 char long, non-numeric string');
        }
        $this->parameters['currency'] = $currency;
    }

    public function setOrderID($orderId)
    {
        if (strlen($orderId) > 10) {
            throw new InvalidArgumentException('The Order ID should not exceed 10 chars in length');
        }
        $this->parameters['orderID'] = $orderId;
    }
    
    public function setPaymentMethod($paymentMethod)
    {
        if ($this->allowedMethods && !in_array($paymentMethod, $this->allowedMethods)) {
            throw new UnsupportedException('The paymentmethod "' . $paymentMethod . '" is not included in the allowed methods');
        }
        $this->parameters['paymentMethod'] = $paymentMethod;
    }

    public function setIssuer($issuer)
    {
        $this->parameters['issuer'] = $issuer;
    }

    public function setReference($reference)
    {
        $this->parameters['reference'] = $reference;
    }

    public function setDescription($description)
    {
        $this->parameters['description'] = $description;
    }

    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            $field = lcfirst(substr($method, 3));
            if(array_key_exists($field, $this->parameters)) {
                return $this->parameters[lcfirst($field)];
            }
        }
        throw new BadMethodCallException('Unkown method ' . $method);
    }

    public function getArray()
    {
        $this->validate();
        return $this->parameters;
    }

    public function validate()
    {
        foreach ($this->requiredParameters as $field) {
            if (!isset($this->parameters[ $field ])) {
                throw new MissingParameterException('"' . $field . '" is required');
            }
        }
        $this->validatePaymentMethodParameters();
    }
    
    protected function validatePaymentMethodParameters()
    {
        if (!empty($this->parameters['paymentMethod'])) {
            $methodClassName = 'Icepay_Paymentmethod_' . ucfirst(strtolower($this->parameters['paymentMethod']));
            /** @var \Icepay_Paymentmethod $paymentMethod */
            $paymentMethod = new $methodClassName();
            
            if (isset($this->parameters['country']) && $country = $this->parameters['country']) {
                if (!$this->isSupportedBy($country, $paymentMethod->getSupportedCountries())) {
                    throw new UnsupportedException('The country "' . $country . '" is not supported by payment method "' . $this->parameters['paymentMethod'] . '"');
                }
            }

            if (isset($this->parameters['language']) && $language = $this->parameters['language']) {
                if (!$this->isSupportedBy($language, $paymentMethod->getSupportedLanguages())) {
                    throw new UnsupportedException('The language "' . $language . '" is not supported by payment method "' . $this->parameters['paymentMethod'] . '"');
                }
            }

            if (isset($this->parameters['amount']) && $amount = $this->parameters['amount']) {
                $amountRange = $paymentMethod->getSupportedAmountRange();
                if($amount < $amountRange['minimum']) {
                    throw new InvalidArgumentException('The minimum amount for payment method "'.$this->parameters['paymentMethod'].'" is '.$amountRange['minimum']);
                }
                if($amount > $amountRange['maximum']) {
                    throw new InvalidArgumentException('The maximum amount for payment method "'.$this->parameters['paymentMethod'].'" is '.$amountRange['maximum']);
                }
            }

            if (isset($this->parameters['currency']) && $currency = $this->parameters['currency']) {
                if (!$this->isSupportedBy($currency, $paymentMethod->getSupportedCurrency())) {
                    throw new UnsupportedException('The currency "' . $currency . '" is not supported by payment method "' . $this->parameters['paymentMethod'] . '"');
                }
            }

            if (isset($this->parameters['issuer']) && $issuer = $this->parameters['issuer']) {
                if (!$this->isSupportedBy($issuer, $paymentMethod->getSupportedIssuers())) {
                    throw new UnsupportedException('The issuer "' . $issuer . '" is not supported by payment method "' . $this->parameters['paymentMethod'] . '"');
                }
            }
        }
    }

    protected function isSupportedBy($needle, array $haystack = array())
    {
        $result = true;
        if ($haystack && $haystack[0] != "00") {
            $result = in_array($needle, $haystack);
        }
        return $result;
    }
}
