<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNet\Message\Query;

use CartThrob\Dependency\Omnipay\Common\Message\AbstractRequest;
use CartThrob\Dependency\Omnipay\Common\Message\AbstractResponse;
use CartThrob\Dependency\Omnipay\Omnipay;
/**
 * Authorize.Net AIM Response
 */
abstract class AbstractQueryResponse extends AbstractResponse
{
    /**
     * http://bookofzeus.com/articles/convert-simplexml-object-into-php-array/
     *
     * Convert a simpleXMLElement in to an array
     *
     * @todo this is duplicated from CIMAbstractResponse. Put somewhere shared.
     *
     * @param \SimpleXMLElement $xml
     *
     * @return array
     */
    public function xml2array(\SimpleXMLElement $xml)
    {
        return \json_decode(\json_encode($xml), \true);
        $arr = array();
        foreach ($xml as $element) {
            $tag = $element->getName();
            $e = \get_object_vars($element);
            if (!empty($e)) {
                $arr[$tag][] = $element instanceof \SimpleXMLElement ? $this->xml2array($element) : $e;
            } else {
                $arr[$tag] = \trim($element);
            }
        }
        return $arr;
    }
}
