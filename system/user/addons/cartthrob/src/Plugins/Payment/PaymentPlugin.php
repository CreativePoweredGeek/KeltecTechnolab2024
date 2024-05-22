<?php

namespace CartThrob\Plugins\Payment;

use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Plugins\Plugin;
use CartThrob\Transactions\TransactionState;
use Cartthrob_payments;
use Cartthrob_token;

use function ee;

use ExpressionEngine\Service\Validation\Validator;

/**
 * @method string responseUrl($gateway, $segments = array())
 * @method void saveCartSnapshot($orderId, $inventoryProcess = false, $discountsProcessed = false)
 * @method array setOrderMeta($orderId, $status = null, $eeStatus = null, $transactionId = null, $errorMessage = null, $data = array())
 * @method void setPurchasedItemsStatus($eeStatus, $orderId, $transactionId = null)
 * @method string jumpForm($url, $fields = array(), $hideJumpForm = true, $title = false, $overview = false, $submitText = false, $fullPage = true, $hiddenFields = array())
 * @method mixed round($number)
 * @method void setStatus($status, $state, $orderId, $sendEmail = true)
 * @method void processCart()
 * @method void clearCart($cartId = null)
 * @method mixed relaunchCart($cartId = null, $orderId = null)
 * @method int|bool updateVaultData($data, $id = null)
 * @method mixed subscriptionInfo($data, $key, $default = false)
 * @method array requiredFields()
 * @method array setGateway($gateway)
 * @method array loadLang($which, $path = null)
 * @method mixed curlTransaction($url, $data = false, $header = false, $mode = 'POST', $suppressErrors = false, $options = null)
 * @method string curlPost($url, $params = array(), $options = array())
 * @method string customerId()
 * @method mixed order($key = false)
 * @method mixed orderId()
 * @method void relaunchSessionFull($sessionId)
 * @method mixed getLangAbbr($lang)
 * @method array|null relaunchCartSnapshot($orderId)
 * @method string getNotifyUrl($gateway, $method = false)
 * @method void completePaymentOffsite($url, $offsiteData = array(), $formSubmission = false)
 * @method mixed getOrderStatus($orderId)
 * @method array|bool|void checkoutStart($options)
 * @method Cartthrob_payments addError($key, $value = false)
 * @method mixed apply($module, $function)
 * @method bool module_enabled($module)
 * @method bool moduleEnabled($module)
 * @method mixed gateway()
 * @method mixed total()
 * @method string themeFolderUrl($pathSuffix = '')
 * @method mixed createMember($options = array())
 * @method void saveMemberWithOrder($memberId, $orderId, $orderData = null)
 * @method string updateOrderById($orderId, $data)
 * @method Cartthrob_token|TransactionState createToken($creditCardNumber)
 * @method void setMemberGroup($memberId, $groupId)
 * @method void activateMember($memberId, $groupId = null)
 * @method void checkoutCompleteOffsite($state, $orderId, $completionType = null)
 * @method void checkoutComplete($state, $template, $return, $stopProcessing)
 * @method bool|mixed hook($hook, $params = null)
 * @method void updateSubscriptions($data, $id = null)
 * @method TransactionState refund($transactionId, $amount, $creditCardNumber)
 * @method TransactionState charge($creditCardNumber)
 * @method TransactionState chargeToken($token, $customerId, $offsite)
 * @method TransactionState createRecurrentBilling($amount, $creditCardNumber, $subData)
 * @method TransactionState updateRecurrentBilling($id, $creditCardNumber)
 * @method TransactionState deleteRecurrentBilling($id)
 */
abstract class PaymentPlugin extends Plugin
{
    /**
     * @var string
     */
    public const DEFAULT_ERROR_MESSAGE = '';

    public $title = '';
    public $overview = '';
    public $settings = [];
    public $required_fields = [];
    public $fields = [];

    public $vault_fields = [];
    public $hidden = [];
    public $card_type = [];
    public $html = '';
    public $language_file = false;
    public $payment_details_available = false;
    public $form_extra = '<script src="' . URL_THIRD_THEMES . 'cartthrob/scripts/cartthrob-tokenizer.js"></script>
        <script>CartthrobTokenizer.init();</script>';

    /*
     * Fields that should not be included in the post.  This will strengthen PCI compliance and prevent
     * credit card information from reaching the server
     */
    public array $nameless_fields = [];
    public array $extra_fields = [];

    /** @var Cartthrob_payments */
    protected $core;

    /**
     * So you can call cartthrob_payments methods more easily
     *
     * @param $method
     * @param $args
     * @return bool|mixed
     */
    public function __call($method, $args)
    {
        try {
            if (!method_exists($this->core, $method)) {
                throw new Exception('Call to undefined method %s::%s() in %s on line %s');
            } elseif (!is_callable([$this->core, $method])) {
                throw new Exception('Call to private method %s::%s() in %s on line %s');
            }
        } catch (Exception $e) {
            $backtrace = $e->getTrace();
            $backtrace = $backtrace[1];

            return trigger_error(sprintf($e->getMessage(), $backtrace['class'], $backtrace['function'], $backtrace['file'], $backtrace['line']));
        }

        return call_user_func_array([$this->core, $method], $args);
    }

    public function initialize($params = [], $defaults = [])
    {
        // Pass
    }

    /**
     * Plugin settings accessor
     *
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function plugin_settings($key, $default = false)
    {
        $settings = ee()->cartthrob->store->config(get_class($this) . '_settings');

        if ($key === false) {
            return $settings ? $settings : $default;
        }

        return Arr::get($settings, $key, $default);
    }

    /**
     * @param Cartthrob_payments $core
     * @return $this
     */
    public function set_core($core)
    {
        if (is_object($core)) {
            $this->core = $core;
        }

        return $this;
    }

    public function form_extra()
    {
        return $this->form_extra;
    }

    /**
     * @param string $transactionId
     * @return TransactionState
     */
    protected function authorize(string $transactionId)
    {
        return (new TransactionState())->setAuthorized()->setTransactionId($transactionId);
    }

    /**
     * @param string $transactionId
     * @return TransactionState
     */
    protected function processing(string $transactionId)
    {
        return (new TransactionState())->setProcessing()->setTransactionId($transactionId);
    }

    /**
     * @param string|null $message
     * @return TransactionState
     */
    protected function fail(string $message = null)
    {
        return (new TransactionState())->setFailed($message ?: ee()->lang->line(static::DEFAULT_ERROR_MESSAGE));
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);
        $data = $this->data;
        $validator->defineRule('whenModeIs', function ($key, $value, $parameters, $rule) use ($data) {
            return ($data['mode'] == $parameters[0]) ? true : $rule->skip();
        });

        return $validator;
    }
}
