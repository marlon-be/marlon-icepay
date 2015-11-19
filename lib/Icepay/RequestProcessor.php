<?php

namespace Icepay;

use Icepay\Exception\ApiException;

class RequestProcessor {

    /** @var MerchantID */
    protected $merchantId;

    /** @var SecretCode */
    protected $secretCode;

    /** @var string url user should be directed to if the transaction was successful */
    protected $successUrl;

    /** @var string url user should be directed to if something went wrong in the transaction */
    protected $errorUrl;

    // Return values

    /** @var string url user should be directed to for the transaction */
    protected $url;

    /** @var string */
    protected $icepayTransactionId;

    /** @var string */
    protected $providerTransactionId;

    /** @var string (Y/N) */
    protected $testMode;

    public function __construct(MerchantID $merchantId, SecretCode $secret, $successUrl = null, $errorUrl = null)
    {
        $this->merchantId = $merchantId;
        $this->secretCode = $secret;
        $this->successUrl = $successUrl;
        $this->errorUrl = $errorUrl;
    }

    public function process(PaymentRequest $request)
    {
        $paymentObject = new \Icepay_PaymentObject();
        foreach ($request->getArray() as $name => $value) {
            call_user_func(
                array($paymentObject, 'set' . ucfirst($name)),
                $value
            );
        }

        if ($request->getPaymentMethod()) {
            $this->processWebservice($paymentObject);
        } else {
            $this->processBasic($paymentObject);
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getIcepayTransactionId()
    {
        return $this->icepayTransactionId;
    }

    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    protected function processBasic(\Icepay_PaymentObject_Interface_Abstract $paymentObject)
    {
        try {
            $basicmode = \Icepay_Basicmode::getInstance()
                ->setMerchantID((string)$this->merchantId)
                ->setSecretCode((string)$this->secretCode)
                ->validatePayment($paymentObject);

            $this->url = $basicmode->getURL();
            $this->icepayTransactionId = null;
            $this->providerTransactionId = null;
            $this->testMode = null;

        } catch (\Exception $e) {
            throw new ApiException('There was a problem processing the basic payment', 0, $e);
        }
    }

    protected function processWebservice(\Icepay_PaymentObject_Interface_Abstract $paymentObject)
    {
        try {
            /** @var \Icepay_Webservice_Pay $service */
            $service = \Icepay_Api_Webservice::getInstance()->paymentService();
            $service->setMerchantID((string)$this->merchantId)
                ->setSecretCode((string)$this->secretCode)
                ->setSuccessURL($this->successUrl)
                ->setErrorURL($this->errorUrl);
            /** @var \Icepay_TransactionObject $transactionObj */
            $transactionObj = $service->checkOut($paymentObject);

            $this->url = $transactionObj->getPaymentScreenURL();
            $this->icepayTransactionId = $transactionObj->getPaymentID();
            $this->providerTransactionId = $transactionObj->getProviderTransactionID();
            $this->testMode = $transactionObj->getTestMode();

        } catch (\Exception $e) {
            throw new ApiException('There was a problem procesing the Webservice payment', 0, $e);
        }
    }
}
 