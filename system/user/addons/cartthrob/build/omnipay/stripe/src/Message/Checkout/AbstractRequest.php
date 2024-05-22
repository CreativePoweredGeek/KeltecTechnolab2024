<?php

/**
 * Stripe Abstract Request.
 */
namespace CartThrob\Dependency\Omnipay\Stripe\Message\Checkout;

/**
 * Stripe Payment Intent Abstract Request.
 *
 * This is the parent class for all Stripe payment intent requests.
 * It adds just a getter and setter.
 *
 * @see \Omnipay\Stripe\PaymentIntentsGateway
 * @see \Omnipay\Stripe\Message\AbstractRequest
 * @link https://stripe.com/docs/api/payment_intents
 */
abstract class AbstractRequest extends \CartThrob\Dependency\Omnipay\Stripe\Message\AbstractRequest
{
}
