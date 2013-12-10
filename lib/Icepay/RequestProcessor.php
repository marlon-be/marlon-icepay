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
        $paymentObject->setData((object)$request->getArray());

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

    protected function processBasic(\Icepay_PaymentObjectInterface $paymentObject)
    {
        try {
            $basicmode = \Icepay_Basicmode::getInstance()
                ->setMerchantID($this->merchantId)
                ->setSecretCode($this->secretCode)
                ->validatePayment($paymentObject);

            $this->url = $basicmode->getURL();
            $this->icepayTransactionId = null;
            $this->providerTransactionId = null;
            $this->testMode = null;

        } catch (\Exception $e) {
            throw new ApiException('There was a problem processing the basic payment', 0, $e);
        }
    }

    protected function processWebservice(\Icepay_PaymentObjectInterface $paymentObject)
    {
        try {
            /** @var \Icepay_Webservice_Pay $service */
            $service = \Icepay_Api_Webservice::getInstance()->paymentService();
            $service->setMerchantID($this->merchantId)
                ->setSecretCode($this->secretCode)
                ->setSuccessURL($this->successUrl)
                ->setErrorURL($this->errorUrl);
            /** @var \Icepay_Webservice_TransactionObject $transactionObj */
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
 