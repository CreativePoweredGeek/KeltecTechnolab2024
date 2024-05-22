<?php

use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Dependency\Omnipay\Common\Helper as OmnipayHelper;
use CartThrob\PaymentGateways\AbstractPaymentGateway;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Services\PluginService;
use CartThrob\Transactions\TransactionState;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Cartthrob_payments class
 *
 * This class executes gateway methods for CartThrob
 * This class does NOT return information about a gateway. For that purpose use Api_cartthrob_payments_gateways.php
 *
 * Usage: (in this example a gateway is set, and the gateway createToken method is executed);
 *
 * Cartthrob_payments->setGateway(gateway_name)->createToken(params);
 *
 **/
class Cartthrob_payments
{
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_OFFSITE = 'offsite';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_VOIDED = 'voided';

    public const COMPLETION_TYPE_RETURN = 'return';
    public const COMPLETION_TYPE_TEMPLATE = 'template';
    public const COMPLETION_TYPE_STOP = 'stop_processing';

    public $cartthrob;
    public $store;
    public $cart;
    public $pending_group_id = 4;
    private $paths = [];
    private $errors = [];
    private $total;
    private $orderStatus = null;
    private $thirdPartyPath;
    private $modules = [];

    /** @var AbstractPaymentGateway */
    private $gateway;

    /** @var string */
    private $gatewayMethod;

    protected ?string $userLang = null;

    public function __construct($params = [])
    {
        $this->paths[] = CARTTHROB_GATEWAY_PLUGIN_PATH;

        if (!function_exists('json_decode')) {
            ee()->load->library('services_json');
        }

        $available_modules = [
            'subscriptions',
        ];

        foreach ($available_modules as $module) {
            $class = 'Cartthrob_' . $module;
            $shortName = strtolower($class);

            if (file_exists(PATH_THIRD . $shortName . '/libraries/' . $class . '.php')) {
                ee()->load->add_package_path(PATH_THIRD . $shortName . '/');
                ee()->load->library($shortName);

                $this->modules[$module] = &ee()->$shortName;

                ee()->load->remove_package_path(PATH_THIRD . $shortName . '/');
            } else {
                $this->modules[$module] = false;
            }
        }

        loadCartThrobPath();

        // loading these here, because it looks like the package path is lost at some point causing the loading of these later to fail.
        ee()->load->library('logger');
        ee()->load->library('form_builder');
        ee()->load->library('cartthrob_emails');
        ee()->load->library('template_helper');
        ee()->load->helper(['array', 'countries', 'data_formatting']);
    }

    /**
     * Compose gateway reponse URL
     *
     * @param $gateway
     * @param array $query
     * @return string
     */
    public static function responseUrl($gateway, $query = [])
    {
        if (str_starts_with($gateway, 'Cartthrob_')) {
            $gateway = substr($gateway, 10);
        }

        ee()->load->library('paths');
        $query = array_merge(['gateway' => $gateway], $query);
        $extload = ee()->paths->build_action_url('Cartthrob', 'extload_action', $query);

        return $extload;
    }

    /**
     * Get the first error
     *
     * @return string|false
     */
    public function error()
    {
        return reset($this->errors);
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return ee()->cartthrob->cart->total();
    }

    /**
     * Set the total for gateways that need the total when the checkout form is rendered, such as stripe
     *
     * @param $total
     * @return Cartthrob_payments
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Load third party libraries, usually api wrappers, in payment_gateways/vendor
     *
     * @return string
     */
    public function libraryPath()
    {
        return PATH_THIRD . 'cartthrob/payment_gateways/libraries/';
    }

    /**
     * Get the base URL to the CartThrob theme folder
     *
     * @param string $pathSuffix
     * @return mixed
     */
    public function themeFolderUrl($pathSuffix = '')
    {
        return ee()->config->item('theme_folder_url') . $pathSuffix;
    }

    /**
     * Get the payment URL
     *
     * @return string
     */
    public function paymentUrl()
    {
        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Cartthrob', 'checkout_action');
    }

    /**
     * Get payment gateway paths
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Charge a credit card with the active gateway
     *
     * @param $cardNumber
     * @return TransactionState
     */
    public function charge($cardNumber)
    {
        $cardNumber = sanitize_credit_card_number($cardNumber);
        $state = new TransactionState();

        if ($this->total <= 0) {
            $state->setAuthorized()->setTransactionId(time());
        } elseif (!$this->gateway) {
            $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        } else {
            $state = $this->gateway->charge($cardNumber);
        }

        return $state;
    }

    /**
     * Check if the method passed method exists on the active gateway class
     *
     * @param $method
     * @return bool
     */
    public function isValidGatewayMethod($method)
    {
        return $this->gateway && method_exists($this->gateway, $method) && is_callable([$this->gateway, $method]);
    }

    /**
     * Refund a transaction
     *
     * @param string|null $transactionId
     * @param string|null $amount
     * @param string|null $creditCardNumber
     * @param array $extra
     * @return TransactionState
     */
    public function refund($transactionId = null, $amount = null, $creditCardNumber = null, array $extra = [])
    {
        $state = new TransactionState();

        if (!$this->gateway) {
            if (ee()->extensions->active_hook('cartthrob_on_refund_failure') === true) {
                ee()->extensions->call('cartthrob_on_refund_failure', ee()->lang->line('invalid_payment_gateway'));
            }

            return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        }

        if (!($amount + 0)) {
            $amount = null;
        }

        if (!$this->isValidGatewayMethod('refund')) {
            if (ee()->extensions->active_hook('cartthrob_on_refund_failure') === true) {
                ee()->extensions->call('cartthrob_on_refund_failure', ee()->lang->line('gateway_refund_not_supported'));
            }

            return $state->setFailed(ee()->lang->line('gateway_refund_not_supported'));
        }

        if (ee()->extensions->active_hook('cartthrob_on_pre_refund_attempt') === true) {
            ee()->extensions->call('cartthrob_on_pre_refund_attempt', $transactionId, $amount);
        }

        $result = $this->gateway->refund($transactionId, $amount, $creditCardNumber, $extra);

        if (ee()->extensions->active_hook('cartthrob_on_post_refund_attempt') === true) {
            ee()->extensions->call('cartthrob_on_post_refund_attempt', $result, $transactionId, $amount);
        }

        return $result;
    }

    /**
     * Charge a token with the active payment gateway
     *
     * @param $token
     * @param null $customerId
     * @param bool $offsite
     * @return TransactionState
     */
    public function chargeToken($token, $customerId = null, $offsite = false)
    {
        $state = new TransactionState();

        if ($this->total <= 0) {
            return $state->setAuthorized()->setTransactionId(time());
        }

        if (!$this->gateway) {
            return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        }

        if ($this->isValidGatewayMethod('chargeToken')) {
            return $this->gateway->chargeToken($token, $customerId, $offsite);
        }

        return $state->setFailed(ee()->lang->line('gateway_charge_token_not_supported'));
    }

    /**
     * @param $amount
     * @param $creditCardNumber
     * @param $subData
     * @return TransactionState
     */
    public function createRecurrentBilling($amount, $creditCardNumber, $subData)
    {
        $state = new TransactionState();

        if ($this->isValidGatewayMethod('createRecurrentBilling')) {
            return $this->gateway->createRecurrentBilling($amount, $creditCardNumber, $subData);
        }

        return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
    }

    /**
     * @param $id
     * @param $creditCardNumber
     * @return TransactionState
     */
    public function updateRecurrentBilling($id, $creditCardNumber)
    {
        $state = new TransactionState();

        if (!$this->isValidGatewayMethod('updateRecurrentBilling') && !$this->isValidGatewayMethod('update_recurrent_billing')) {
            return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        }

        ee()->load->model('order_model');
        ee()->load->model('vault_model');

        if ($this->isValidGatewayMethod('updateRecurrentBilling')) {
            $state = $this->gateway->updateRecurrentBilling($id, $creditCardNumber);
        }

        if ($state->isAuthorized()) {
            $data = [];

            if ($transactionId = $state->getTransactionId()) {
                $data['sub_id'] = $transactionId;
            }

            ee()->vault_model->update($data, $id);
        }

        return $state;
    }

    /**
     * @param $data
     * @param null $id
     * @return int|bool
     */
    public function updateVaultData($data, $id = null)
    {
        if (!is_array($data)) {
            return false;
        }

        ee()->load->model('vault_model');

        return ee()->vault_model->update($data, $id);
    }

    /**
     * @param $id
     * @return TransactionState
     */
    public function deleteRecurrentBilling($id)
    {
        $state = new TransactionState();

        if (!$this->gateway && !is_callable([$this->gateway, 'deleteRecurrentBilling'])) {
            return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
        }

        ee()->load->model('vault_model');

        $state = $this->gateway->deleteRecurrentBilling($id);

        if ($state->isAuthorized()) {
            ee()->vault_model->delete_vault(null, null, null, $id);
        }

        return $state;
    }

    /**
     * @param $data
     * @param $key
     * @param bool $default
     * @return bool|mixed
     */
    public function subscriptionInfo($data, $key, $default = false)
    {
        return Arr::get($data, $key, $default);
    }

    /**
     * @return array
     */
    public function requiredGatewayFields()
    {
        return $this->gateway ? $this->gateway->required_fields : [];
    }

    /**
     * @param $which
     * @param string|null $path
     */
    public function loadLang($which, $path = null)
    {
        if (is_null($path)) {
            $path = PATH_THIRD . 'cartthrob/';
        }

        if (is_null($this->userLang)) {
            if (!empty(ee()->session->userdata['language'])) {
                $this->userLang = ee()->session->userdata['language'];
            } elseif (ee()->input->cookie('language')) {
                $this->userLang = ee()->input->cookie('language');
            } else {
                $this->userLang = ee()->config->item('deft_lang') ? ee()->config->item('deft_lang') : 'english';
            }

            $this->userLang = ee()->security->sanitize_filename($this->userLang);
        }

        ee()->lang->load($which, $this->userLang, false, true, $path, false);
    }

    /**
     * @param $msg
     * @param bool $type
     * @return mixed
     */
    public function log($msg, $type = false)
    {
        ee()->load->model('log_model');

        return ee()->log_model->log($msg, $type);
    }

    /**
     * @param $url
     * @param bool $data
     * @param bool $header
     * @param string $mode
     * @param bool $suppressErrors
     * @param null $options
     * @return bool|string|void
     */
    public function curlTransaction($url, $data = false, $header = false, $mode = 'POST', $suppressErrors = false, $options = null)
    {
        if (!function_exists('curl_exec')) {
            return show_error(lang('curl_not_installed'));
        }

        // CURL Data to institution
        $curl = curl_init($url);

        if (ee()->config->item('cartthrob:curl_proxy')) {
            curl_setopt($curl, CURLOPT_PROXY, ee()->config->item('cartthrob:curl_proxy'));

            if (ee()->config->item('cartthrob:curl_proxy_port')) {
                curl_setopt($curl, CURLOPT_PROXYPORT, ee()->config->item('cartthrob:curl_proxy_port'));
            }
        }

        if ($header) {
            if (!is_array($header)) {
                $header = [$header];
            }

            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        } else {
            // set to 0 to eliminate header info from response
            curl_setopt($curl, CURLOPT_HEADER, 0);
        }

        // Returns response data instead of TRUE(1)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($data) {
            if ($mode === 'POST') {
                // use HTTP POST to send form data
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            } else {
                // check for query  string
                if (strrpos($url, '?') === false) {
                    curl_setopt($curl, CURLOPT_URL, $url . '?' . $data);
                } else {
                    curl_setopt($curl, CURLOPT_URL, $url . $data);
                }

                curl_setopt($curl, CURLOPT_HTTPGET, 1);
            }
        } else {
            // if there's no data passed in, then it's a GET
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
        }

        // Turn off the server and peer verification (PayPal TrustManager Concept).
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }

        // execute post and get results
        $response = curl_exec($curl);

        if (!$response) {
            $error = curl_error($curl) . ' (' . curl_errno($curl) . ')';
        }

        curl_close($curl);

        if (!$suppressErrors && !empty($error)) {
            return show_error($error);
        }

        return $response;
    }

    /**
     * @param $url
     * @param array $params
     * @param array $options
     * @return string
     */
    public function curlPost($url, $params = [], $options = [])
    {
        if (is_array($url)) {
            $options = (isset($url[2])) ? $url[2] : [];
            $params = (isset($url[1])) ? $url[1] : [];
            $url = $url[0];
        }

        ee()->load->library('curl');

        return ee()->curl->simple_post($url, $params, $options);
    }

    /**
     * @param $url
     * @param array $options
     * @return string
     */
    public function curlGet($url, $options = [])
    {
        if (is_array($url)) {
            $options = (isset($url[1])) ? $url[1] : [];
            $url = $url[0];
        }

        ee()->load->library('curl');

        return ee()->curl->simple_get($url, $options);
    }

    /**
     * @return mixed
     */
    public function curlErrorMessage()
    {
        ee()->load->library('curl');

        return ee()->curl->error_string;
    }

    /**
     * @return mixed
     */
    public function curlErrorCode()
    {
        ee()->load->library('curl');

        return ee()->curl->error_code;
    }

    /**
     * @return mixed
     */
    public function customerId()
    {
        return ee()->session->userdata('member_id');
    }

    /**
     * Get the order ID from the order
     *
     * @return mixed
     */
    public function orderId()
    {
        return $this->order('order_id');
    }

    /**
     * Get the order from the cart
     *
     * @param bool $key
     * @return mixed
     */
    public function order($key = false)
    {
        return ee()->cartthrob->cart->order($key);
    }

    /**
     * @param $lang
     * @return mixed
     */
    public function getLangAbbr($lang)
    {
        ee()->load->library('languages');

        return ee()->languages->get_language_abbrev($lang);
    }

    /**
     * @param $sessionId
     */
    public function relaunchSession($sessionId)
    {
        if ($sessionId != @session_id()) {
            @session_destroy();
            @session_id($sessionId);
            @session_start();
        }

        ee()->load->model('order_model');

        $orderId = ee()->order_model->getOrderIdFromSession($sessionId);

        $this->relaunchCartSnapshot($orderId);
    }

    /**
     * NOTE: Remember that the cart has to have been saved first. This happens automatically in gateway exit offsite
     *       using saveCartSnapshot(). If that's not used though, you'll have to manually save the cart.
     *
     * @param $orderId
     * @return array|null
     */
    public function relaunchCartSnapshot($orderId)
    {
        ee()->load->model('order_model');

        $data = ee()->order_model->getCartFromOrder($orderId);

        if ($data) {
            ee()->remove('cartthrob');
            ee()->set('cartthrob', Cartthrob_core::instance('ee', ['cart' => $data]));

            return $data;
        }

        return null;
    }

    /**
     * @param $gateway
     * @param bool $method
     * @return string
     */
    public function getNotifyUrl($gateway, bool $method = false): string
    {
        if (str_starts_with($gateway, 'Cartthrob_')) {
            $gateway = substr($gateway, 10);
        }

        $notifyUrl = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER
            . 'ACT=' . ee()->functions->insert_action_ids(ee()->functions->fetch_action_id('Cartthrob', 'payment_return_action'))
            . '&G=' . base64_encode(ee('Encrypt')->encode($gateway));

        if ($method) {
            $notifyUrl .= '&M=' . base64_encode(ee('Encrypt')->encode($method));
        }

        return $notifyUrl;
    }

    /**
     * Send the user off-site for handling the rest of the transaction.
     * Add this function at the bottom of your charge() function.
     *
     * @param array $offsiteData
     * @param string|null $url
     * @param bool $formSubmission Do the offsite redirect as a form submission when true. Otherwise, HTTP redirect.
     */
    public function completePaymentOffsite(?string $url, array $offsiteData = [], bool $formSubmission = false)
    {
        $state = (new TransactionState())->setProcessing(ee()->lang->line('status_offsite'));

        $this->saveCartSnapshot($this->order('entry_id'));
        $this->setStatus(self::STATUS_OFFSITE, $state, $this->order('order_id'), $emailData = false);

        if ($formSubmission) {
            exit($this->jumpForm(
                $url,
                $offsiteData,
                $hideJumpForm = true,
                ee()->lang->line('jump_header'),
                ee()->lang->line('jump_alert'),
                ee()->lang->line('jump_submit')
            ));
        }

        if (count($offsiteData) > 0) {
            $url .= '?' . http_build_query($offsiteData);
        }

        ee()->functions->redirect($url);
    }

    /**
     * @param $orderId
     * @param bool $inventoryProcess
     * @param bool $discountsProcessed
     */
    public function saveCartSnapshot($orderId, $inventoryProcess = false, $discountsProcessed = false)
    {
        ee()->load->model('order_model');

        // for backward compatibility I'm saving the session id in the order table.
        // systems that previously used session id to relaunch the session will at least be able to
        // continue to use the same identifier. The CT session will be relaunched using the order id tied to the session.
        $sessionId = @session_id();

        if (!$sessionId) {
            @session_start();
            $sessionId = @session_id();
        }

        ee()->order_model->saveCartSnapshot(
            $orderId,
            $inventoryProcess,
            $discountsProcessed,
            ee()->cartthrob->cart_array(),
            ee()->cartthrob->cart->id(),
            $sessionId
        );
    }

    /**
     * @param $orderId
     * @param string|null $status
     * @param string|null $eeStatus
     * @param string|null $transactionId
     * @param string|null $errorMessage
     * @param array $data
     */
    public function setOrderMeta($orderId, $status = null, $eeStatus = null, $transactionId = null, $errorMessage = null, $data = [])
    {
        ee()->load->model('order_model');

        if (!is_null($status)) {
            if ($status === self::STATUS_AUTHORIZED || $status === self::STATUS_COMPLETED) {
                ee()->order_model->updateOrder($orderId, ['cart' => '']); // garbage cleanup
            }

            ee()->order_model->setOrderStatus($orderId, $status);
        }

        if (!is_null($transactionId)) {
            ee()->order_model->setOrderTransactionId($orderId, $transactionId);
        }

        if (!is_null($errorMessage)) {
            ee()->order_model->setOrderErrorMessage($orderId, $errorMessage);
        }

        if (ee()->cartthrob->store->config('save_orders')) {
            if (!is_null($eeStatus)) {
                $data['status'] = $eeStatus;
            }

            if (!is_null($transactionId)) {
                $data['transaction_id'] = $transactionId;
            }

            if (!is_null($errorMessage)) {
                $data['error_message'] = $errorMessage;
            }

            ee()->order_model->updateOrder($orderId, $data);
        }
    }

    /**
     * @param null $status
     */
    public function setPurchasedItemsStatus($status)
    {
        if (empty($status) || !$this->shouldSavePurchasedItems()) {
            return;
        }

        ee()->load->model('purchased_items_model');

        foreach ($this->order('purchased_items') as $entryId) {
            if (is_array($entryId)) {
                if (array_key_exists('entry_id', $entryId)) {
                    $var = null;
                    $entryId = $var = $entryId['entry_id'];
                } else {
                    // @TODO... this should be an error
                    return;
                }
            }

            ee()->purchased_items_model->update_purchased_item($entryId, compact('status'));
        }
    }

    /**
     * Generate a form to in-browser redirect the user to the payment gateway for checkout
     *
     * @param $url
     * @param array $fields
     * @param bool $hideJumpForm
     * @param bool $title
     * @param bool $overview
     * @param bool $submitText
     * @param bool $fullPage
     * @param array $hiddenFields
     * @return string
     */
    public function jumpForm($url, $fields = [], $hideJumpForm = true, $title = false, $overview = false, $submitText = false, $fullPage = true, $hiddenFields = [])
    {
        if ($overview === false) {
            $overview = ee()->lang->line('jump_alert');
        }
        if ($title === false) {
            $title = ee()->lang->line('jump_header');
        }
        if ($submitText === false) {
            $submitText = ee()->lang->line('jump_finish');
        }

        if ($fullPage) {
            $html[] = "
                <html><head>
                <script type='text/javascript'>
                    window.onload = function(){ document.forms[0].submit(); };
                </script>
                </head></html>
            ";
        }

        if ($hideJumpForm) {
            // hiding contents from JS users.
            $html[] = "<script type='text/javascript'>document.write('<div style=\'display:none\'>');</script>";
        }

        if ($fullPage) {
            $html[] = '<h1>' . $title . '</h1>';
            $html[] = '<p>' . $overview . '</p>';
        }

        $html[] = "<form name='jump' id='jump' method='POST' action='{$url}' >";

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                // authorize.net SIM requries the same field be sent over and over for line items
                foreach ($value as $subkey => $subvalue) {
                    $html[] = "<input type='text' name='{$key}' value='{$subvalue}' />";
                }
            } else {
                $html[] = "<input type='text' name='{$key}' value='{$value}' />";
            }
        }

        foreach ($hiddenFields as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $html[] = "<input type='hidden' name='{$key}' value='{$subvalue}' />";
                }
            } else {
                $html[] = "<input type='hidden' name='{$key}' value='{$value}' />";
            }
        }

        $html[] = "<input type='submit' value='{$submitText}' />";
        $html[] = '</form>';

        if ($hideJumpForm) {
            $html[] = "<script type='text/javascript'>document.write('</div>');</script>";
        }

        if ($fullPage) {
            $html[] = '</body></html>';
        }

        return implode('', $html);
    }

    /**
     * Set the status of an order
     *
     * @param $status
     * @param TransactionState $state
     * @param $orderId
     * @param array|bool $emailData
     */
    public function setStatus($status, $state, $orderId, $emailData = true)
    {
        $methodName = sprintf('process%sState', ucfirst($status));

        $this->$methodName($state, $orderId, $emailData);
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getOrderStatus($orderId)
    {
        if ($this->orderStatus == null) {
            ee()->load->model('order_model');

            $this->orderStatus = ee()->order_model->getOrderStatus($orderId);
        }

        return $this->orderStatus;
    }

    /**
     * Process the discounts and inventory on the cart
     */
    public function processCart()
    {
        ee()->cartthrob
            ->process_discounts()
            ->process_inventory();
    }

    /**
     * Clear the cart
     *
     * @param string|null $cartId
     */
    public function clearCart($cartId = null)
    {
        if ($cartId) {
            $this->relaunchCart($cartId);
        }

        ee()->cartthrob->cart
            ->clearAll()
            ->save();
    }

    /**
     * @param null $cartId
     * @param null $orderId
     * @return array|null
     */
    public function relaunchCart($cartId = null, $orderId = null)
    {
        if ($orderId && !$cartId) {
            ee()->load->model('order_model');
            $cartId = ee()->order_model->getOrderCartId($orderId);
        }

        ee()->load->model('cart_model');

        $data = ee()->cart_model->fetch($cartId);

        if ($data) {
            ee()->remove('cartthrob');
            ee()->set('cartthrob', Cartthrob_core::instance('ee', ['cart' => $data]));
            ee()->load->library('cartthrob_session', [
                'core' => $this,
                'use_regenerate_id' => false,
                'use_fingerprint' => false,
            ]);
            ee()->cartthrob_session->set_cart_id($cartId);

            if (!empty($data['language'])) {
                ee()->load->library('languages');
                ee()->languages->set_language($data['language']);
            }

            return $data;
        }

        return null;
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processCanceledState($state, $orderId, $emailData)
    {
        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        $this->setOrderMeta(
            $orderId,
            self::STATUS_CANCELED,
            ee()->cartthrob->store->config('orders_status_canceled') ?: 'closed',
            $state->getTransactionId(),
            $state->getMessage()
        );

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_canceled') ?: 'closed');
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_CANCELED, ee()->cartthrob->cart->order());

        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processOffsiteState($state, $orderId, $emailData = false)
    {
        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        $this->setOrderMeta(
            $orderId,
            self::STATUS_OFFSITE,
            ee()->cartthrob->store->config('orders_status_offsite') ?: 'closed',
            $state->getTransactionId(),
            $state->getMessage()
        );

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_offsite') ?: 'closed');
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_OFFSITE, ee()->cartthrob->cart->order());

        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processAuthorizedState($state, $orderId, $emailData = false)
    {
        if ($this->getOrderStatus($orderId) == self::STATUS_COMPLETED || $this->getOrderStatus($orderId) == self::STATUS_AUTHORIZED) {
            return;
        }

        $updateData = [
            'status' => ee()->cartthrob->store->config('orders_default_status') ?: 'open',
            'transaction_id' => $state->getTransactionId(),
        ];

        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_AUTHORIZED,
                $updateData['status'],
                $state->getTransactionId(),
                $state->getMessage(),
                $updateData
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_authorized') ?: 'open');

        if (ee()->extensions->active_hook('cartthrob_on_authorize') === true) {
            ee()->extensions->call('cartthrob_on_authorize');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->cartthrob->cart->save();
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_COMPLETED, ee()->cartthrob->cart->order());
    }

    /**
     * Set order status to declined
     *
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processDeclinedState($state, $orderId, $emailData)
    {
        if ($this->getOrderStatus($orderId) == self::STATUS_COMPLETED || $this->getOrderStatus($orderId) == self::STATUS_AUTHORIZED) {
            return;
        }

        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_DECLINED,
                ee()->cartthrob->store->config('orders_declined_status') ?: 'closed',
                $state->getTransactionId(),
                ee()->lang->line('declined') . ': ' . $state->getMessage()
            );
        }

        if ($this->order('purchased_items')) {
            $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_declined_status') ?: 'closed');
        }

        if (ee()->extensions->active_hook('cartthrob_on_decline') === true) {
            ee()->extensions->call('cartthrob_on_decline');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->cartthrob->cart->save();
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_DECLINED, ee()->cartthrob->cart->order());
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processExpiredState($state, $orderId, $emailData)
    {
        if ($this->getOrderStatus($orderId) != self::STATUS_COMPLETED && $this->getOrderStatus($orderId) != self::STATUS_AUTHORIZED) {
            ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

            if (ee()->cartthrob->store->config('save_orders')) {
                $this->setOrderMeta(
                    $orderId,
                    self::STATUS_EXPIRED,
                    ee()->cartthrob->store->config('orders_status_expired') ?: 'closed',
                    $state->getTransactionId(),
                    $state->getMessage()
                );
            }

            $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_expired') ?: 'closed');
            ee('cartthrob:NotificationsService')->dispatch(self::STATUS_EXPIRED, ee()->cartthrob->cart->order());
        }

        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processFailedState($state, $orderId, $emailData)
    {
        if ($this->getOrderStatus($orderId) == self::STATUS_COMPLETED || $this->getOrderStatus($orderId) == self::STATUS_AUTHORIZED) {
            return;
        }

        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_FAILED,
                ee()->cartthrob->store->config('orders_failed_status') ?: 'closed',
                $state->getTransactionId(),
                ee()->lang->line('failed') . ': ' . $state->getMessage()
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_failed_status') ?: 'closed');

        if (ee()->extensions->active_hook('cartthrob_on_fail') === true) {
            ee()->extensions->call('cartthrob_on_fail');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->cartthrob->cart->save();
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_FAILED, ee()->cartthrob->cart->order());
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processPendingState($state, $orderId, $emailData)
    {
        if ($this->getOrderStatus($orderId) != self::STATUS_COMPLETED && $this->getOrderStatus($orderId) != self::STATUS_AUTHORIZED) {
            ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

            if (ee()->cartthrob->store->config('save_orders')) {
                $this->setOrderMeta(
                    $orderId,
                    self::STATUS_PENDING,
                    ee()->cartthrob->store->config('orders_status_pending') ?: 'closed',
                    $state->getTransactionId(),
                    $state->getMessage()
                );
            }

            $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_pending') ?: 'closed');

            ee('cartthrob:NotificationsService')->dispatch(self::STATUS_PENDING, ee()->cartthrob->cart->order());
        }

        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processProcessingState($state, $orderId, $emailData)
    {
        if ($this->getOrderStatus($orderId) == self::STATUS_COMPLETED || $this->getOrderStatus($orderId) == self::STATUS_AUTHORIZED || $this->getOrderStatus($orderId) == self::STATUS_PENDING) {
            return;
        }

        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_PROCESSING,
                ee()->cartthrob->store->config('orders_processing_status') ?: 'closed',
                $state->getTransactionId(),
                ee()->lang->line('processing') . ': ' . $state->getMessage()
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_processing_status') ?: 'closed');

        if (ee()->extensions->active_hook('cartthrob_on_processing') === true) {
            ee()->extensions->call('cartthrob_on_processing');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->cartthrob->cart->save();
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_PROCESSING, ee()->cartthrob->cart->order());
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processRefundedState($state, $orderId, $emailData)
    {
        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_REFUNDED,
                ee()->cartthrob->store->config('orders_status_refunded') ?: 'closed',
                $state->getTransactionId(),
                $state->getMessage()
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_refunded') ?: 'closed');

        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_REFUNDED, $emailData);

        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processReversedState($state, $orderId, $emailData)
    {
        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_REVERSED,
                ee()->cartthrob->store->config('orders_status_reversed') ?: 'closed',
                $state->getTransactionId(),
                $state->getMessage()
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_reversed') ?: 'closed');

        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_REVERSED, ee()->cartthrob->cart->order());
        ee()->cartthrob->cart->save();
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param array|bool $emailData
     */
    private function processVoidedState($state, $orderId, $emailData)
    {
        ee()->cartthrob->cart->update_order(['auth' => $state->toArray()]);

        if (ee()->cartthrob->store->config('save_orders')) {
            $this->setOrderMeta(
                $orderId,
                self::STATUS_VOIDED,
                ee()->cartthrob->store->config('orders_status_voided') ?: 'closed',
                $state->getTransactionId(),
                $state->getMessage()
            );
        }

        $this->setPurchasedItemsStatus(ee()->cartthrob->store->config('purchased_items_status_voided') ?: 'closed');

        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_VOIDED, ee()->cartthrob->cart->order());
        ee()->cartthrob->cart->save();
    }

    /**
     * @param $options
     * @return bool|TransactionState|void
     */
    public function asyncCheckoutStart($options)
    {
        if (ee()->cartthrob->cart->is_empty() && !ee()->config->item('cartthrob:allow_empty_cart_checkout')) {
            $this->addError(lang('empty_cart'));

            return false;
        }

        if (null === $this->setGateway(element('gateway', $options))->gateway) {
            $this->addError(lang('invalid_payment_gateway'));

            return false;
        }

        if (false === $this->validUser($options)) {
            return false;
        }

        if (ee()->extensions->active_hook('cartthrob_pre_process') === true) {
            $options = ee()->extensions->call('cartthrob_pre_process', $options) ?? $options;

            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->load->model('order_model');
        $order = ee()->order_model->create_async_order();
        $data = $this->doCalculations($options);
        $data['order_id'] = $data['entry_id'] = $order['entry_id'];
        ee()->cartthrob->cart->set_order($data);

        return $this->processPayment(element('credit_card_number', $options));
    }

    /**
     * Validate and then attempt to charge the payment
     *
     * @param array $options
     * @return TransactionState|bool False if errors are encountered. Errors can be found in $cartthrob_payments->errors(), otherwise $state array
     */
    public function checkoutStart($options)
    {
        ee()->load->library('form_builder');
        ee()->load->model('order_model');

        $state = new TransactionState();

        // rebill
        $orderId = element('order_id', $options);

        // rebill
        $subscriptionId = element('subscription_id', $options);
        $isSubscriptionRebill = element('is_subscription_rebill', $options);

        // admin update
        $updateOrderId = element('update_order_id', $options);

        // subscription update
        $updateSubscriptionId = element('update_subscription_id', $options);

        // 2 whether this is a sub or not basedon the subscription id. $sub
        // $order_data needs to be from entry.
        // member_id set in options

        // this is to update an order by passing in the order id. this has nothing to do with Rebills or Subscriptions
        if ($updateOrderId) {
            if (ee()->order_model->canUpdateOrder($updateOrderId)) {
                $data = array_merge(
                    ee()->order_model->get_order_from_entry($updateOrderId),
                    ee()->cartthrob->cart->customer_info()
                );

                // relaunch the cart from this order
                ee()->cartthrob->cart = Cartthrob_core::create_child(ee()->cartthrob, 'cart', $data);

                // is this data not IN the order_data already?
                $orderEntry = ee()->order_model->getOrder($orderId);
                $options['member_id'] = $orderEntry['author_id'];

                unset($orderEntry);

                $orderId = $updateOrderId;
            } else {
                $this->addError(lang('you_do_not_have_sufficient_permissions_to_update_this_order'));

                return false;
            }
        } elseif ($orderId) {
            $data = $this->apply('subscriptions', 'subscription_order_data', $orderId);
        } elseif ($updateSubscriptionId) {
            ee()->load->model('subscription_model');

            $subscription = ee()->subscription_model->get_subscription($updateSubscriptionId);
            $tempOrderId = element('order_id', $subscription);

            if (ee()->order_model->canUpdateOrder($tempOrderId)) {
                $data = ee()->order_model->get_order_from_entry($tempOrderId);
                $data = array_merge($data, ee()->cartthrob->cart->customer_info());
                // is this data not IN the order_data already?
                $data['subscription_options'] = element('subscription_options', $options);

                if (element('allow_modification', $data['subscription_options'])) {
                    unset($data['subscription_options']['allow_modification']);
                }

                $data['subscription'] = element('subscription', $options);
                $orderEntry = ee()->order_model->getOrder($tempOrderId);
                $options['member_id'] = $orderEntry['author_id'];
                $options['gateway'] = element('payment_gateway', $data);

                // relaunch the cart from this order
                ee()->cartthrob->cart = Cartthrob_core::create_child(ee()->cartthrob, 'cart', $data);

                unset($orderEntry);
            } else {
                $this->addError(lang('you_do_not_have_sufficient_permissions_to_update_this_order') . ' c2');

                return false;
            }
        }

        if (!$updateSubscriptionId && !$subscriptionId && empty($data) && ee()->cartthrob->cart->is_empty() && !ee()->config->item('cartthrob:allow_empty_cart_checkout')) {
            $this->addError(lang('empty_cart'));

            return false;
        }

        if ($gateway = element('gateway', $options)) {
            $this->setGateway($gateway);
        }

        if (!$this->gateway()) {
            $this->addError(lang('invalid_payment_gateway'));

            return false;
        }

        if ($gatewayMethod = element('gateway_method', $options)) {
            $this->setGatewayMethod($gatewayMethod);
        }

        ee()->cartthrob->cart->check_inventory();

        if (ee()->cartthrob->errors()) {
            $this->addError(ee()->cartthrob->errors());

            return false;
        }

        $creditCardNumber = element('credit_card_number', $options);

        ee()->load->library('api/api_cartthrob_tax_plugins');

        $expirationDate = element('expiration_date', $options);
        $groupId = element('group_id', $options, 5);

        if (false === $this->validUser($options)) {
            return false;
        }

        if (!empty($data)) {
            $this->setTotal($data['total']);
        } else {
            $data = $this->doCalculations($options);
        }

        if (ee()->extensions->active_hook('cartthrob_pre_process') === true) {
            $options = ee()->extensions->call('cartthrob_pre_process', $options) ?? $options;

            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!$updateSubscriptionId && ee()->cartthrob->store->config('save_orders')) {
            // this is passed from process_subscription
            if (isset($options['subscription_options']) && !empty($options['subscription_options'])) {
                $data['entry_id'] = null;
                $data['auth'] = [];
                $data['invoice_number'] = null;
                $data['title'] = null;
                $data['transaction_id'] = null;
                $data['processing'] = null;
                $data['authorized'] = null;
                $data['declined'] = null;
                $data['failed'] = null;
                $data['error_message'] = null;

                $shipping = ee()->cartthrob->cart->shipping();
                $subtotal = ee()->cartthrob->cart->subtotal();
                $discount = ee()->cartthrob->cart->discount();
                $tax = ee()->cartthrob->cart->tax();

                $subtotalPlusTax = $subtotal + $tax;
                $subtotalPlusShipping = $subtotal + $shipping;
                $shippingPlusTax = ee()->cartthrob->cart->shipping_plus_tax();
                $taxRate = $subtotalPlusShipping > 0 ? $tax / ($subtotal + $shipping) : 0;

                $data['shipping'] = $shipping;
                $data['discount'] = $discount;
                $data['tax'] = $tax;
                $data['subtotal'] = $subtotal;
                $data['subtotal_plus_tax'] = $subtotalPlusTax;
                $data['subtotal_plus_shipping'] = $subtotalPlusShipping;
                $data['tax_rate'] = $taxRate;
                $data['shipping_plus_tax'] = $shippingPlusTax;
                $data['subscription_id'] = $options['subscription_options']['id'];

                if (element('member_id', $options)) {
                    $data['member_id'] = element('member_id', $options);
                    $data['author_id'] = element('member_id', $options);
                }

                // @TODO confirm that this is setting the total the way we want, and not recalculating everythin
                $total = ee()->cartthrob->cart->total();
                $data['total'] = $total;
                $orderId = null;
                $this->setTotal($data['total']);
            }

            if (!$orderId) {
                if (!empty($expirationDate)) {
                    $data['expiration_date'] = $expirationDate;
                }

                ee()->load->model('order_model');

                $orderEntry = ee()->order_model->createOrder($data);
                $data['entry_id'] = $data['order_id'] = $orderEntry['entry_id'];
                $data['title'] = $data['invoice_number'] = $orderEntry['title'];

                unset($data['expiration_date']);
            }
        } else {
            $data['title'] = $data['invoice_number'] = '';
        }

        // save order to session
        ee()->cartthrob->cart->set_order($data);

        // you can provide a vault in the options array, instead of fetching/creating one
        $vault = element('vault', $options);
        $forceVault = bool_string(element('force_vault', $options));

        /**
         * Subscriptions Start
         */
        $hasSubscription = $this->apply(
            'subscriptions',
            'subscriptions_initialize',
            element('subscription', $options),
            element('subscription_options', $options, [])
        );

        /**
         * Subscriptions End
         */
        $memberId = element('member_id', $options, ee()->session->userdata('member_id'));

        if ($hasSubscription || $forceVault) {
            // no member data here. create a random member
            if (!$memberId && !isset($options['create_user'])) {
                $options['create_user'] = true; // this will tell the next bit to create a member
            }

            // creating and logging in the user if there's a sub / vault
            if (isset($options['create_user']) && $options['create_user'] == true) {
                if (!$memberId) {
                    $memberId = $this->createMember($options['create_user']);
                    unset($options['create_user']);
                }

                $groupId = '4';

                if (element('create_group_id', $options) && !empty($memberId)) {
                    $groupId = element('create_group_id', $options);
                }

                // have to set the member group here, or they can't be logged in
                ee()->cartthrob_members_model->set_member_group($memberId, $groupId);

                // admins... you get booted
                // we're logging this person in... if there's an error and they "create_user" again, it'll be ignored, because they're logged in already.
                ee()->cartthrob_members_model->login_member($memberId);

                ee()->session->cache['cartthrob']['member_id'] = $memberId;

                if (!empty($data['order_id'])) {
                    $this->saveMemberWithOrder($memberId, $data['order_id'], $this->order());
                }
            }

            // if there's not already a vault provided, fetch an existing one
            // if there's not an existing vault, make one
            if (!$vault || $updateSubscriptionId) {
                ee()->load->model('vault_model');

                $vault = ee()->vault_model->get_member_vault($memberId, $gateway, substr($creditCardNumber, -4));

                // if we're updating or there's not vault saved either.. create one.
                if ($updateSubscriptionId || (!$vault || empty($vault['token']))) {
                    // if this is an offsite token generation system like SagePay server, we lose them here. checkout complete offsite needs to handle this.
                    $token = $this->createToken($creditCardNumber);
                    if ($token instanceof Cartthrob_token && $token->error_message()) {
                        return (new TransactionState())->setFailed($token->error_message());
                    } elseif ($token instanceof TransactionState) {
                        if (!$error = $token->getMessage()) {
                            $error = ee()->lang->line('token_method_returning_bad_response');
                        }

                        return (new TransactionState())->setFailed($error);
                    }

                    $newVault = [
                        'customer_id' => $token->customer_id(),
                        'token' => $token->token(),
                        'order_id' => ee()->cartthrob->cart->order('order_id'),
                        'member_id' => ee()->cartthrob_members_model->get_member_id(),
                        'gateway' => $gateway,
                        'last_four' => substr($creditCardNumber, -4),
                    ];

                    if (!empty($vault['id'])) {
                        // if we were returned something without a token, we don't want to update this
                        // this might happen if a member existed, and were somehow using the vault id of a different member.
                        // not that we want THAT to happen either by accident, but it's possible it might happen on purpose.
                        if (!empty($newVault['token'])) {
                            $vault['id'] = ee()->vault_model->update($newVault, $vault['id']);
                        }
                    } elseif (!empty($newVault['token'])) {
                        $vault['id'] = ee()->vault_model->update($newVault);
                    }

                    if (!empty($newVault['token']) && !empty($vault['id']) && $updateSubscriptionId) {
                        $subUpdateData['vault_id'] = $vault['id'];
                        ee()->load->model('subscription_model');
                        ee()->subscription_model->update($subUpdateData, $updateSubscriptionId);
                    }

                    $vault = array_merge($vault, $newVault);
                }
            }
        }

        if ($vault) {
            ee()->cartthrob->cart->update_order(['vault_id' => $vault['id']]);
            ee()->order_model->updateOrder(ee()->cartthrob->cart->order('entry_id'), ['vault_id' => $vault['id']]);
            ee()->cartthrob->cart->save();
        }

        if (element('force_processing', $options)) {
            return $state->setProcessing();
        }

        if ($updateSubscriptionId) {
            ee()->cartthrob->cart->update_order(['subscription_update_id' => $updateSubscriptionId]);
        }

        if ($vault && !$updateSubscriptionId) {
            if (isset($token) && $token->offsite()) {
                $state = $this->chargeToken($vault['token'], $vault['customer_id'], $offsite = true);
            } else {
                $state = $this->chargeToken($vault['token'], $vault['customer_id']);
            }

            if (!$state->isAuthorized()) {
                // this is a bad token. We need to disable it.
                // this could cause problems if
                // skip if this is a subscription rebill
                if (!$isSubscriptionRebill && isset($vault['id']) && $vault['id']) {
                    if ($state->isFailed() || $state->isDeclined()) {
                        // by deleting this, it's possible that rebills using the same token will fail, unless the user immediately updates their vault
                        ee()->load->model('vault_model');
                        ee()->vault_model->update(['token' => null], $vault['id']);
                    }
                }

                return $state;
            }
        } elseif ($updateSubscriptionId) {
            // if it's offsite, we need to bill it now to get the token. otherwise a token isn't actually created
            if (isset($token) && $token->offsite()) {
                ee()->cartthrob->cart->set_meta('last_bill_date', ee()->localize->now);
                ee()->cartthrob->cart->set_meta('used_occurrences', 1); // we'll figure out whether this is trial or regular price later

                $state = $this->chargeToken($vault['token'], $vault['customer_id'], $offsite = true);
            } else {
                $state->setAuthorized();
            }
        } else {
            $state = $this->processPayment($creditCardNumber);
        }

        return $state;
    }

    /**
     * @param $key
     * @param bool $value
     * @return $this
     */
    public function addError($key, $value = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->addError($k, $v);
            }
        } elseif ($value === false) {
            $this->errors[] = $key;
        } else {
            $this->errors[$key] = $value;
        }

        return $this;
    }

    /**
     * @param $module
     * @param $function
     * @return bool|mixed
     */
    public function apply($module, $function)
    {
        if (!$this->moduleEnabled($module)) {
            return false;
        }

        if (!method_exists($this->modules[$module], $function) || !is_callable([$this->modules[$module], $function])) {
            return false;
        }

        $args = func_get_args();

        return call_user_func_array([$this->modules[$module], $function], array_slice($args, 2));
    }

    /**
     * @param $module
     * @return bool
     */
    public function moduleEnabled($module)
    {
        return !empty($this->modules[$module]);
    }

    /**
     * @param string $gateway
     * @return $this
     */
    public function setGateway($gateway)
    {
        if (strpos($gateway, 'Cartthrob_') !== 0) {
            $gateway = 'Cartthrob_' . $gateway;
        }

        if (!is_object($this->gateway) || get_class($this->gateway) != $gateway) {
            $this->gateway = null;

            $this->loadGatewayByPath($gateway);

            if (!$this->gateway) {
                $this->loadGatewayByPluginService($gateway);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function gateway()
    {
        return $this->gateway;
    }

    /**
     * @param $method
     * @return $this
     */
    public function setGatewayMethod($method)
    {
        $this->gatewayMethod = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function gatewayMethod()
    {
        return $this->gatewayMethod;
    }

    /**
     * @return mixed
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function createMember($options = [])
    {
        ee()->load->model('cartthrob_members_model');

        // could accidentally be a boolean if member details weren't already put together.
        if (!$options || !is_array($options)) {
            // no user data. lets create some
            $options = ee()->cartthrob_members_model->generate_random_member_data();
        }

        ee()->cartthrob->cart->update_order(['create_user' => $options]);

        $options['group_id'] ??= $this->pending_group_id;

        return ee()->cartthrob_members_model->create($options);
    }

    /**
     * @param $memberId
     * @param $orderId
     * @param null $orderData
     */
    public function saveMemberWithOrder($memberId, $orderId, $orderData = null)
    {
        ee()->load->model('cartthrob_members_model');

        ee()->cartthrob->cart->update_order(['member_id' => $memberId]);

        $this->updateOrderById($orderId, ['author_id' => $memberId]);

        ee()->cartthrob->cart->save();
        ee()->cartthrob->save_customer_info();

        if (ee()->cartthrob->store->config('save_member_data') && $orderData) {
            ee()->cartthrob_members_model->update($memberId, $orderData);
        }
    }

    /**
     * Update an order entry's data
     *
     * @param string $orderId
     * @param array $data
     * @return string
     */
    public function updateOrderById($orderId, $data)
    {
        ee()->load->model('order_model');

        return ee()->order_model->updateOrder($orderId, $data);
    }

    /**
     * @param $creditCardNumber
     * @return Cartthrob_token|TransactionState
     */
    public function createToken($creditCardNumber)
    {
        $state = new TransactionState();

        if ($this->isValidGatewayMethod('createToken')) {
            return $this->gateway->createToken($creditCardNumber);
        }

        return $state->setFailed(ee()->lang->line('invalid_payment_gateway'));
    }

    // @TODO sage uses cancelled status. need to update this to handle that.
    // @TODO make sure that ee()->cartthrob->cart->whatever works. Might need

    /**
     * @param $memberId
     * @param $groupId
     */
    public function setMemberGroup($memberId, $groupId)
    {
        ee()->load->model('cartthrob_members_model');

        ee()->cartthrob_members_model->set_member_group($memberId, $groupId);
    }

    /**
     * @param $memberId
     * @param null $groupId
     */
    public function activateMember($memberId, $groupId = null)
    {
        ee()->load->model('cartthrob_members_model');

        ee()->cartthrob_members_model->activate_member($memberId, $groupId);
    }

    /**
     * @param TransactionState $state
     * @param string $orderId
     * @param string|null $completionType
     */
    public function checkoutCompleteOffsite($state, $orderId, $completionType = null)
    {
        $templateUrl = null;
        $returnUrl = null;
        $stopProcessing = false;

        $this->relaunchCart(null, $orderId);

        switch ($completionType) {
            case self::COMPLETION_TYPE_RETURN:
                $returnUrl = $this->order('return');
                break;
            case self::COMPLETION_TYPE_TEMPLATE:
                // authorized_return, declined_return, failed_return don't work with this.
                // So stop using it. we should deprecate those anyway.
                $templateUrl = $this->order('return');
                break;
            case self::COMPLETION_TYPE_STOP:
                // some gateways like sage server, output an "OK" status and a redirect URL and need to stop there.
                $stopProcessing = true;
                break;
        }

        $this->checkoutComplete($state, $templateUrl, $returnUrl, $stopProcessing);
    }

    /**
     * @param TransactionState $state
     * @param null $template
     * @param null $return
     * @param bool $stopProcessing
     */
    public function asyncCheckoutComplete(TransactionState $state, $template = null, $return = null, $stopProcessing = false)
    {
        if (!$state->isAuthorized() && !$state->isProcessing()) {
            $this->checkoutComplete($state);

            return;
        }

        $cart = ee()->cartthrob->cart_array();
        ee()->cartthrob->cart->update_order($cart['order'])->save();

        ee()->load->model('async_job_model');
        ee()->async_job_model->create($state, $cart, $_POST);

        $cartmeta = ee()->cartthrob->cart->meta();
        $this->clearCart();
        ee('cartthrob:NotificationsService')->dispatch(self::STATUS_PROCESSING, ee()->cartthrob->cart->order());

        if (!$template && !$return) {
            ee()->form_builder->set_return(ee()->cartthrob->cart->order('processing_redirect'));
        }

        if (ee()->extensions->active_hook('cartthrob_order_created') === true) {
            $data = ee()->extensions->call('cartthrob_order_created', $order, $cartmeta);
        }

        if ($return) {
            if (!preg_match('#^https?://#', $return)) {
                $return = ee()->functions->create_url($return);
            }

            ee()->functions->redirect($return);
            exit;
        } elseif ($template) {
            exit(ee()->template_helper->parse_template(ee()->template_helper->fetch_template($template)));
        } elseif ($stopProcessing) {
            // return. if we exit; it'll make it so that gateways can't do their own thing.
            return;
        }

        ee()->form_builder->action_complete(false, true);
    }

    /**
     * @param TransactionState $state
     * @param string null $template
     * @param null $return
     * @param bool $stopProcessing
     */
    public function checkoutComplete($state, $template = null, $return = null, $stopProcessing = false)
    {
        loadCartThrobPath();

        $updateData = [];
        $secureForms = true;

        /*
         * NOTES: regarding an active session
         * 1.  logging in customer requires an active session. if run from a cul-de-sac payment gateway, the user won't be logged-in when they leave the gateway
         * 2. Process discounts & inventory. Does this requires an active session. If so the session needs to be relaunched to handle this.
         * so...use checkoutCompleteOffsite
         */

        ee()->session->set_flashdata($state->toArray());

        // @TODO, Why is this here?

        ee()->cartthrob->cart->update_order($state);

        $orderId = ee()->cartthrob->cart->order('order_id');

        // since we use the authorized variables as tag conditionals in submitted_order_info,
        // we won't throw any errors from here on out
        ee()->form_builder->set_show_errors(false);

        if (isset($_POST['ERR'])) {
            unset($_POST['ERR']);
        }

        $isAdmin = in_array(ee()->session->userdata('group_id'), ee()->config->item('cartthrob:admin_checkout_groups'));
        $adminId = $isAdmin ? ee()->session->userdata('member_id') : null;

        // checking to see if this is already complete to keep from getting multiple emails or other processing duplication errors.
        ee()->load->model('order_model');

        $orderStatus = ee()->order_model->getOrderStatus($orderId);

        // update
        if (ee()->cartthrob->cart->order('subscription_update_id')) {
            $this->apply('subscriptions', 'subscriptions_start', $state);
            $this->apply('subscriptions', 'subscriptions_complete', $state);

            ee()->load->model('order_model');

            $orderData = ee()->order_model->order_data_array([]);
            $orderData = array_merge($orderData, ee()->cartthrob->cart->customer_info());
            $orderData['title'] = $orderData['items'] = $orderData['custom_data'] = $orderData['subscription_options'] = $orderData['invoice_number'] = '';
            $orderData = array_filter($orderData); // getting rid of the empties.

            ee()->order_model->updateOrder($orderId, $orderData);
            ee()->cartthrob->cart->set_order($orderData);
            ee()->cartthrob->cart->update_order($orderData);
            ee()->cartthrob->cart->save();

            if (ee()->cartthrob->store->config('save_orders')) {
                $this->setOrderMeta(
                    $orderId,
                    self::STATUS_AUTHORIZED,
                    ee()->cartthrob->store->config('orders_default_status') ?: 'open',
                    $state->getTransactionId(),
                    $state->getMessage()
                );
            }

            if (!$template && !$return) {
                ee()->form_builder->set_return(ee()->cartthrob->cart->order('authorized_redirect'));
            }
        } elseif (ee()->cartthrob->cart->order('existing_subscription_items')) { // rebill
            $this->apply('subscriptions', 'subscriptions_start', $state);
            $this->apply('subscriptions', 'subscriptions_complete', $state);

            if ($state->isAuthorized()) {
                $this->setStatus(self::STATUS_AUTHORIZED, $state, $orderId, $emailData = false);
            }
        } elseif ($orderStatus === self::STATUS_AUTHORIZED || $orderStatus === self::STATUS_COMPLETED) {
            if (ee()->cartthrob->store->config('save_orders')) {
                $this->setOrderMeta(
                    $orderId,
                    self::STATUS_AUTHORIZED,
                    ee()->cartthrob->store->config('orders_default_status') ?: 'open',
                    $state->getTransactionId(),
                    $state->getMessage()
                );
            }

            if (!$template && !$return) {
                ee()->form_builder->set_return(ee()->cartthrob->cart->order('authorized_redirect'));
            }
        } else {
            $this->apply('subscriptions', 'subscriptions_start', $state);

            if (!$state->isAuthorized()) {
                $this->apply('subscriptions', 'subscriptions_complete', $state);
            }

            $this->savePurchasedItems($orderId);

            if ($state->isAuthorized()) {
                $memberId = $this->getMemberId($adminId, $updateData, $orderId, $secureForms);

                $this->apply('subscriptions', 'subscriptions_complete', $state);

                $this->setStatus(self::STATUS_AUTHORIZED, $state, $orderId);
                $this->setPermissions();

                ee()->cartthrob->process_discounts()->process_inventory();

                $this->clearCart();

                if (!$template && !$return) {
                    ee()->form_builder
                        ->set_return(ee()->cartthrob->cart->order('authorized_redirect'));
                }
            } elseif ($state->isProcessing()) {
                $this->setStatus(self::STATUS_PROCESSING, $state, $orderId);
                $this->clearCart();

                if (!$template && !$return) {
                    ee()->form_builder
                        ->set_return(ee()->cartthrob->cart->order('processing_redirect'))
                        ->add_error($state->getMessage());
                }
            } elseif ($state->isDeclined()) {
                $this->setStatus(self::STATUS_DECLINED, $state, $orderId);

                if (!$template && !$return) {
                    ee()->form_builder
                        ->set_return(ee()->cartthrob->cart->order('declined_redirect'))
                        ->add_error($state->getMessage());
                }
            } elseif ($state->isFailed()) {
                $this->setStatus(self::STATUS_FAILED, $state, $orderId);

                if (!$template && !$return) {
                    ee()->form_builder
                        ->set_return(ee()->cartthrob->cart->order('failed_redirect'))
                        ->add_error($state->getMessage());
                }
            }
        }

        if (!$isAdmin || !isset($memberId)) {
            ee()->cartthrob->cart->save();
        } elseif ($adminId && isset($memberId)) {  // if you're just an admin, we don't want to log you back in, or else your old cart will pop back up and never erase.
            ee()->load->model('cartthrob_members_model');
            // making sure the admin's logged back in. earlier we log in the new temp user to save their details
            ee()->cartthrob_members_model->login_member($adminId);
            // now we can save the cart.
            // added this in after it came to my attention that the cart was not clearing upon successful transaction for admins
            // saving it... will save the cart clearing.
            ee()->cartthrob->cart->save();
            $secureForms = false; // we have to set this to false, due to EE's use of session id in secure forms checking.
        } else {
            ee()->cartthrob->cart->save();
            $secureForms = false; // we have to set this to false, due to EE's use of session id in secure forms checking.
        }

        if (ee()->extensions->active_hook('cartthrob_order_created') === true) {
            $order = ee()->order_model->getOrder($orderId);
            $cartmeta = ee()->cartthrob->cart->meta();
            $data = ee()->extensions->call('cartthrob_order_created', $order, $cartmeta);
        }

        if ($return) {
            if (!preg_match('#^https?://#', $return)) {
                $return = ee()->functions->create_url($return);
            }

            ee()->functions->redirect($return);
            exit;
        } elseif ($template) {
            exit(ee()->template_helper->parse_template(ee()->template_helper->fetch_template($template)));
        } elseif ($stopProcessing) {
            // return. if we exit; it'll make it so that gateways can't do their own thing.
            return;
        }

        ee()->form_builder->action_complete($validate = false, $secureForms);
    }

    /**
     * This method allows you to call `hook('your_hook', $param1, $param2)` or `hook('your_hook', array($param1, $param2))`
     *
     * @param $hook
     * @param null $params
     * @return bool|mixed
     */
    public function hook($hook, $params = null)
    {
        if (func_num_args() > 2) {
            $params = func_get_args();

            array_shift($params);
        }

        if (!ee()->extensions->active_hook($hook)) {
            return false;
        }

        if (!is_null($params)) {
            if (!is_array($params)) {
                $params = [$params];
            }

            array_unshift($params, $hook);

            return call_user_func_array([ee()->extensions, 'call'], $params);
        }

        return ee()->extensions->call($hook);
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function validUser(array &$options)
    {
        ee()->load->model('cartthrob_members_model');

        $admin = in_array(ee()->session->userdata('group_id'), ee()->config->item('cartthrob:admin_checkout_groups'));

        if ($admin && ee()->cartthrob->cart->customer_info('email_address') == element('create_email', $options)) {
            // admin is checking out with own member info while create_user is turned on.
            // we're tuning create user off
            if (element('create_user', $options)) {
                unset($options['create_user']);
            }
        }

        if (element('create_user', $options) && (!ee()->session->userdata('member_id') || $admin)) {
            // sending the initial set of customer supplied data
            $options['create_user'] = ee()->cartthrob_members_model->validate(
                element('create_username', $options),
                element('create_email', $options),
                element('create_screen_name', $options),
                element('create_password', $options),
                element('create_password_confirm', $options),
                element('create_group_id', $options),
                element('create_language', $options)
            );

            // should only be an FALSE if errors are returned
            if ($options['create_user'] === false) {
                $this->addError(ee()->cartthrob_members_model->errors);

                return false;
            }
        } else {
            // person's already logged in and not an admin.
            // if we leave create user on, some redirect gateways that respawn the cart are left looking
            // to update the member id with a blank member id.
            $options['create_user'] = false;
        }

        return true;
    }

    protected function doCalculations(array $options): array
    {
        ee()->load->model('order_model');

        ee()->cartthrob->cart->set_calculation_caching(false);

        $tax = $options['tax'] ?? ee()->cartthrob->cart->tax();
        $shipping = $options['shipping'] ?? ee()->cartthrob->cart->shipping();
        $subtotal = $options['subtotal'] ?? ee()->cartthrob->cart->subtotal();
        $discount = $options['discount'] ?? ee()->cartthrob->cart->discount();
        $total = $options['total'] ?? ee()->cartthrob->cart->total();

        // only missing if tax or price were manually passed.
        $subtotalPlusTax = $options['subtotal_plus_tax'] ?? $subtotal + $tax;

        // only missing if tax or shipping were manually passed.
        if (isset($options['shipping_plus_tax'])) {
            $shippingPlusTax = $options['shipping_plus_tax'];
        } else {
            $subtotalPlusShipping = $subtotal + $shipping;
            // need to find the effective tax rate, since we may be ignoring the tax plugin itself by using a manual tax value.
            $taxRate = $subtotalPlusShipping > 0 ? $tax / ($subtotal + $shipping) : 0;
            $shippingPlusTax = $shipping + ($taxRate * $shipping);
        }

        $this->setTotal($total);

        return ee()->order_model->order_data_array([
            'shipping' => $shipping,
            'shipping_plus_tax' => $shippingPlusTax,
            'tax' => $tax,
            'subtotal' => $subtotal,
            'subtotal_plus_tax' => $subtotalPlusTax,
            'discount' => $discount,
            'total' => $this->total(),
            'credit_card_number' => element('credit_card_number', $options),
            'create_user' => element('create_user', $options),
            'group_id' => element('group_id', $options, 5),
            'member_id' => ee()->session->userdata('member_id'),
            'subscription' => element('subscription', $options),
            'subscription_options' => element('subscription_options', $options, []),
            'payment_gateway' => $this->gateway,
            'payment_gateway_method' => $this->gatewayMethod,
            'subscription_id' => element('subscription_id', $options),
            'entry_id' => null,
            'auth' => [],
            'invoice_number' => null,
            'title' => null,
            'transaction_id' => null,
            'processing' => null,
            'authorized' => null,
            'declined' => null,
            'failed' => null,
            'error_message' => null,
        ]);
    }

    /**
     * @param string $creditCardNumber
     * @return TransactionState
     */
    protected function processPayment(string $creditCardNumber): TransactionState
    {
        $creditCardNumber = sanitize_credit_card_number($creditCardNumber);

        if ($this->gateway->payment_details_available && ee()->cartthrob->store->config('modulus_10_checking') && !OmnipayHelper::validateLuhn($creditCardNumber)) {
            $msg = ee()->lang->line('validation_card_modulus_10');
            $this->addError($msg);

            return (new TransactionState())->setFailed($msg);
        }

        ee()->cartthrob->cart->save();

        // IF the payment gateway directs users offsite, we will lose them at this point.
        // so the second half of the process is offloaded.
        return $this->charge($creditCardNumber);
    }

    /**
     * @param $orderId
     */
    private function savePurchasedItems($orderId)
    {
        if (!$this->shouldSavePurchasedItems()) {
            return;
        }

        ee()->load->model('purchased_items_model');

        $purchasedItems = [];

        foreach (ee()->cartthrob->cart->order('items') as $rowId => $item) {
            // if it's a package, we'll make purchased items from the sub_items and not the package itself
            if (!empty($item['sub_items'])) {
                foreach ($item['sub_items'] as $_row_id => $_item) {
                    $_item['package_id'] = $item['entry_id'];

                    $purchasedItems[$rowId . ':' . $_row_id] = ee()->purchased_items_model->create_purchased_item(
                        $_item,
                        $orderId,
                        ee()->cartthrob->store->config('purchased_items_default_status')
                    );
                }

                // this will also save the package
                if (ee()->cartthrob->store->config('save_packages_too')) {
                    $purchasedItems[$rowId] = ee()->purchased_items_model
                        ->create_purchased_item($item, $orderId, ee()->cartthrob->store->config('purchased_items_default_status'));
                }
            } else {
                $purchasedItems[$rowId] = ee()->purchased_items_model
                    ->create_purchased_item($item, $orderId, ee()->cartthrob->store->config('purchased_items_default_status'));
            }
        }

        ee()->cartthrob->cart->update_order(['purchased_items' => $purchasedItems]);
    }

    /**
     * @param $adminId
     * @param array $updateData
     * @param $orderId
     * @param bool $secureForms
     * @return mixed
     */
    private function getMemberId($adminId, array $updateData, $orderId, bool &$secureForms)
    {
        ee()->load->model('cartthrob_members_model');

        $memberId = null;

        if (ee()->cartthrob->cart->order('create_user') && (!ee()->session->userdata('member_id') || !is_null($adminId))) {
            $memberId = $this->createMember(ee()->cartthrob->cart->order('create_user'));
            $groupId = element('group_id', ee()->cartthrob->cart->order('create_user'));

            // going to log in this new member and save the data
            if ($adminId) {
                ee()->cartthrob_members_model->login_member($memberId);
                $secureForms = false; // we have to set this to false, due to EE's use of session id in secure forms checking.
            }

            ee()->cartthrob->save_customer_info();
            ee()->cartthrob->cart->save();

            $this->saveMemberWithOrder($memberId, $this->order('entry_id'), $this->order());

            if ($groupId && !empty($memberId)) {
                ee()->cartthrob_members_model->activate_member($memberId, $groupId);
                $secureForms = false; // we have to set this to false, due to EE's use of session id in secure forms checking.
            }

            if ($memberId) {
                ee()->session->cache['cartthrob']['member_id'] = $memberId;

                $updateData['author_id'] = $memberId;
            }

            $this->updateOrderById($orderId, $updateData);
        } elseif (ee()->cartthrob->cart->meta('checkout_as_member')) {
            $memberId = ee()->cartthrob->cart->meta('checkout_as_member');

            // going to log in this new member and save the data
            if ($adminId) {
                ee()->cartthrob_members_model->login_member($memberId);
                $secureForms = false; // we have to set this to false, due to EE's use of session id in secure forms checking.
            }

            ee()->cartthrob->cart->set_meta('checkout_as_member', false);
            ee()->cartthrob->save_customer_info();
            ee()->cartthrob->cart->save();

            $this->saveMemberWithOrder($memberId, $this->order('entry_id'), $this->order());
        }

        return $memberId;
    }

    private function setPermissions(): void
    {
        foreach (ee()->cartthrob->cart->order('items') as $rowId => $item) {
            // subs takes care of its own permissions. skip permission items
            if (!empty($item['meta']['permissions']) && empty($item['meta']['subscription'])) {
                ee()->load->model('permissions_model');

                $perms = [];

                if (!is_array($item['meta']['permissions'])) {
                    $perms = explode('|', $item['meta']['permissions']);
                } elseif (isset($item['meta']['permissions'])) {
                    $perms = (array)$item['meta']['permissions'];
                }

                foreach ($perms as $perm) {
                    ee()->permissions_model->update([
                        'permission' => $perm,
                        'order_id' => ee()->cartthrob->cart->order('entry_id'),
                        'member_id' => ee()->cartthrob_members_model->get_member_id(),
                        'item_id' => $item['product_id'],
                    ]);
                }
            }
        }
    }

    /**
     * @param string $gateway
     */
    private function loadGatewayByPath(string $gateway): void
    {
        foreach ($this->paths as $path) {
            if (!file_exists($path . $gateway . '.php')) {
                continue;
            }

            $this->gateway = new $gateway();
            $this->gateway->set_core($this);

            $this->loadLang(strtolower($gateway));

            $this->gateway->initialize();
        }
    }

    /**
     * @param string $gateway
     */
    private function loadGatewayByPluginService(string $gateway)
    {
        ee('cartthrob:PluginService')
            ->getByType(PluginService::TYPE_PAYMENT)
            ->filter(function (PaymentPlugin $plugin) use ($gateway) {
                return get_class($plugin) === $gateway;
            })
            ->each(function (PaymentPlugin $plugin) {
                $this->gateway = new $plugin();
                $this->gateway->set_core($this);
                $this->gateway->initialize();
            });
    }

    /**
     * @return bool
     */
    private function shouldSavePurchasedItems(): bool
    {
        return ee()->cartthrob->store->config('save_purchased_items') && ee()->cartthrob->store->config('save_orders');
    }
}
