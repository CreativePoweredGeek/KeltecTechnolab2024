<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNet\Message\Query;

use CartThrob\Dependency\Omnipay\Common\Exception\InvalidResponseException;
use CartThrob\Dependency\Omnipay\Common\Message\AbstractRequest;
/**
 * Authorize.Net AIM Response
 */
class QueryBatchResponse extends AbstractQueryResponse
{
    /**
     * For Error codes: @see
     * https://developer.authorize.net/api/reference/responseCodes.html
     */
    const ERROR_RESPONSE_CODE_CANNOT_ISSUE_CREDIT = 54;
    public function __construct(AbstractRequest $request, $data)
    {
        // Strip out the xmlns junk so that PHP can parse the XML.
        $xml = \preg_replace('/<getSettledBatchListRequest[^>]+>/', '<getSettledBatchListRequest>', (string) $data);
        try {
            $xml = \simplexml_load_string($xml, 'SimpleXMLElement', \LIBXML_NOWARNING);
        } catch (\Exception $e) {
            throw new InvalidResponseException();
        }
        if (!$xml) {
            throw new InvalidResponseException();
        }
        parent::__construct($request, $xml);
    }
    public function isSuccessful()
    {
        return 'Ok' === $this->getResultCode();
    }
    public function getResultCode()
    {
        $result = $this->xml2array($this->data->messages, \true);
        return $result['messages'][0]['resultCode'];
    }
    public function getData()
    {
        return $this->xml2array($this->data->batchList, \true)['batch'];
    }
}
