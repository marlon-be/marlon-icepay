<?php

namespace Icepay;

use Icepay\Exception\ApiException;
use Icepay\Exception\InvalidArgumentException;
use Icepay\Exception\InvalidResponseException;

class PaymentResponse {

    /** @var MerchantID */
    protected $merchantId;

    /** @var SecretCode */
    protected $secretCode;

    /** @var ResponseStatus */
    protected $status;

    /** @var string */
    protected $statusDescription;

    /** @var string */
    protected $orderId;

    /** @var array */
    protected $parameters;

    private $getParameters = array(
        'status', 'statusCode', 'merchant', 'orderID',
        'paymentID', 'reference', 'transactionID', 'checksum'
    );

    private $postParameters = array(
        'status', 'statusCode', 'merchant', 'orderID',
        'paymentID', 'reference', 'transactionID', 'consumerName',
        'consumerAccountNumber', 'consumerAddress', 'consumerHouseNumber', 'consumerCity',
        'consumerCountry', 'consumerEmail', 'consumerPhoneNumber', 'consumerIPAddress',
        'amount', 'currency', 'paymentMethod'
    );

    public function __construct(MerchantID $merchantId, SecretCode $secretCode)
    {
        $this->merchantId = $merchantId;
        $this->secretCode = $secretCode;
    }

    public function loadGet()
    {
        $result = new \Icepay_Result();
        $result->setMerchantID((string)$this->merchantId)
                ->setSecretCode((string)$this->secretCode);

        try {
            if ($result->validate()) {
                $this->status = new ResponseStatus($result->getStatus());
                $this->statusDescription = $result->getResultData()->statusCode;
                $this->orderId = $result->getOrderID();
                $this->parameters = (array)$result->getResultData();

            } else {
                throw new InvalidResponseException('Could not verify get response data');
            }

        } catch (\Exception $e) {
            throw new ApiException('There was a problem validating the get response', 0, $e);
        }
    }

    public function loadPost()
    {
        $result = new \Icepay_Postback();
        $result->setMerchantID((string)$this->merchantId)
                ->setSecretCode((string)$this->secretCode);

        try {
            if ($result->validate()) {
                $this->status = new ResponseStatus($result->getStatus());
                $this->statusDescription = $result->getPostback()->statusCode;
                $this->orderId = $result->getOrderID();
                $this->parameters = (array)$result->getPostback();

            } else {
                throw new InvalidResponseException('Could not verify post response data');
            }

        } catch (\Exception $e) {
            throw new ApiException('There was a problem validating the post response', 0, $e);
        }
    }

    public function isSuccessful()
    {
        return (string)$this->status == ResponseStatus::SUCCESS;
    }

    public function isPending()
    {
        return (string)$this->status == ResponseStatus::OPEN;
    }

    public function toArray()
    {
        return $this->parameters;
    }

    public function getStatusDescription()
    {
        return $this->statusDescription;
    }

    /**
     * Retrieves a response parameter
     * @param string $param
     * @throws \InvalidArgumentException
     */
    public function getParam($key)
    {
        if(method_exists($this, 'get'.$key)) {
            return $this->{'get'.$key}();
        }

        // always use uppercase
        $key = strtoupper($key);
        $parameters = array_change_key_case($this->parameters,CASE_UPPER);
        if(!array_key_exists($key, $parameters)) {
            throw new InvalidArgumentException('Parameter ' . $key . ' does not exist.');
        }

        return $parameters[$key];
    }
}
 