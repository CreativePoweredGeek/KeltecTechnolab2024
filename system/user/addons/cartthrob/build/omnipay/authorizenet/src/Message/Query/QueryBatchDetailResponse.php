<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNet\Message\Query;

use CartThrob\Dependency\Omnipay\Common\Exception\InvalidResponseException;
use CartThrob\Dependency\Omnipay\Common\Message\AbstractRequest;
/**
 * Authorize.Net AIM Response
 */
class QueryBatchDetailResponse extends AbstractQueryResponse
{
    public function __construct(AbstractRequest $request, $data)
    {
        // Strip out the xmlns junk so that PHP can parse the XML
        $xml = \preg_replace('/<getTransactionListRequest[^>]+>/', '<getTransactionListRequest>', (string) $data);
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
        return 1 === $this->getResultCode();
    }
    public function getData()
    {
        return $this->xml2array($this->data->transactions, \true)['transaction'];
    }
}
