<?php

namespace Icepay;

use Icepay\Exception\InvalidArgumentException;

class Service {
    const LOG_LEVEL_ALL = 1;
    const LOG_LEVEL_TRANSACTION = 2;
    const LOG_LEVEL_ERRORS = 4;
    const LOG_LEVEL_ERRORS_AND_TRANSACTION = 8;

    /** @var MerchantID */
    protected $merchantId;

    /** @var SecretCode */
    protected $secretCode;

    public function __construct(MerchantID $merchantId, SecretCode $secret)
    {
        $this->merchantId = $merchantId;
        $this->secretCode = $secret;
    }

    public function setLoggingLevel($level)
    {
        if (!in_array($level, array(self::LOG_LEVEL_ALL, self::LOG_LEVEL_ERRORS, self::LOG_LEVEL_ERRORS_AND_TRANSACTION, self::LOG_LEVEL_TRANSACTION))) {
            throw new InvalidArgumentException('The logging level "' . $level . '" is not recognized');
        }
        \Icepay_Api_Logger::getInstance()->setLoggingLevel($level);
    }

    public function setupFileLogger($dir, $file)
    {
        /** @var \Icepay_Api_Logger $logger */
        $logger = \Icepay_Api_Logger::getInstance();
        $logger->enableLogging()
                ->logToFile()
                ->setLoggingDirectory($dir)
                ->setLoggingFile($file);
    }

    public function getPaymentMethods($amount = null, $country = null, $currency = null)
    {
        /** @var \Icepay_Api_Webservice $api */
        $api = \Icepay_Api_Webservice::getInstance();
        $methods = $api->paymentMethodService()
                        ->setMerchantID((string)$this->merchantId)
                        ->setSecretCode((string)$this->secretCode)
                        ->retrieveAllPaymentmethods()->asArray();

        /** @var \Icepay_Webservice_Filtering $filtering */
        $filtering = $api->filtering();
        $filtering->loadFromArray($methods);

        if ($amount) {
            $filtering->filterByAmount($amount);
        }
        if ($country) {
            $filtering->filterByCountry($country);
        }
        if ($currency) {
            $filtering->filterByCurrency($currency);
        }

        return $filtering->getPaymentmethods();
    }

    public function createPaymentRequest($amount = null, $country = null, $currency = null)
    {
        return new PaymentRequest($this->getPaymentMethods($amount, $country, $currency));
    }

    public function createRequestProcessor($successUrl = null, $errorUrl = null)
    {
        return new RequestProcessor($this->merchantId, $this->secretCode, $successUrl, $errorUrl);
    }

    public function createPaymentResponse()
    {
        return new PaymentResponse($this->merchantId, $this->secretCode);
    }
}
 