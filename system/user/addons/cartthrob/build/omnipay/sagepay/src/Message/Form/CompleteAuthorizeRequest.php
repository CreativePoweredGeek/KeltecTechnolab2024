<?php

namespace CartThrob\Dependency\Omnipay\SagePay\Message\Form;

/**
 * Sage Pay Form Complete Authorize Response.
 */
use CartThrob\Dependency\Omnipay\SagePay\Message\AbstractRequest;
use CartThrob\Dependency\Omnipay\SagePay\Message\Response as GenericResponse;
use CartThrob\Dependency\Omnipay\Common\Exception\InvalidResponseException;
class CompleteAuthorizeRequest extends AbstractRequest
{
    /**
     * @return string the transaction type
     */
    public function getTxType()
    {
        if ($this->getUseAuthenticate()) {
            return static::TXTYPE_AUTHORISE;
        } else {
            return static::TXTYPE_RELEASE;
        }
    }
    /**
     * Data will be encrypted as a query parameter.
     *
     * @return array
     * @throws InvalidResponseException if "crypt" is missing or invalid.
     */
    public function getData()
    {
        // The application has the option of passing the query parameter
        // in, perhaps using its own middleware, or allowing Omnipay t0
        // provide it.
        $crypt = $this->getCrypt() ?: $this->httpRequest->query->get('crypt');
        // Make sure we have a crypt parameter before trying to decrypt it.
        if (empty($crypt) || !\is_string($crypt) || \substr($crypt, 0, 1) !== '@') {
            throw new InvalidResponseException('Missing or invalid "crypt" parameter');
        }
        // Remove the leading '@' and decrypt the remainder into a query string.
        // And E_WARNING error will be issued if the crypt parameter data is not
        // a hexadecimal string.
        $hexString = \substr($crypt, 1);
        if (!\preg_match('/^[0-9a-f]+$/i', $hexString)) {
            throw new InvalidResponseException('Invalid "crypt" parameter; not hexadecimal');
        }
        $queryString = \openssl_decrypt(\hex2bin($hexString), 'aes-128-cbc', $this->getEncryptionKey(), \OPENSSL_RAW_DATA, $this->getEncryptionKey());
        \parse_str($queryString, $data);
        return $data;
    }
    /**
     * Nothing to send to gateway - we have the result data in the server request.
     */
    public function sendData($data)
    {
        // The Response in the current namespace conflicts with
        // the Response in the namespace one level down, but only
        // for PHP 5.6. This alias works around it.
        return $this->response = new GenericResponse($this, $data);
    }
    /**
     * @return string The crypt set as an override for the query parameter.
     */
    public function getCrypt()
    {
        return $this->getParameter('cryptx');
    }
    /**
     * @param string $value If set, then used in preference to the current query parameter.
     * @return $this
     */
    public function setCrypt($value)
    {
        return $this->setParameter('cryptx', $value);
    }
}
