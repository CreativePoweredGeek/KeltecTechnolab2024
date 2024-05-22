<?php

namespace CartThrob\Services;

use CartThrob\Dependency\Illuminate\Support\Collection;
use CartThrob\Plugins\Discount\DiscountPlugin;
use CartThrob\Plugins\Notification\NotificationPlugin;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Plugins\Plugin;
use CartThrob\Plugins\Price\PricePlugin;
use CartThrob\Plugins\Shipping\ShippingPlugin;
use CartThrob\Plugins\Tax\TaxPlugin;
use CartThrob\Services\Exceptions\PluginRegistrationException;

/**
 * @method getDiscount(string $className)
 * @method getPayment(string $className)
 * @method getPrice(string $className)
 * @method getShipping(string $className)
 * @method getTax(string $className)
 */
class PluginService
{
    public const TYPE_DISCOUNT = 'discount';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_PRICE = 'price';
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_TAX = 'tax';
    public const TYPE_NOTIFICATION = 'notification';

    /** @var Collection */
    private $plugins;

    public function __construct()
    {
        $this->plugins = new Collection([
            self::TYPE_DISCOUNT => new Collection(),
            self::TYPE_PAYMENT => new Collection(),
            self::TYPE_PRICE => new Collection(),
            self::TYPE_SHIPPING => new Collection(),
            self::TYPE_TAX => new Collection(),
            self::TYPE_NOTIFICATION => new Collection(),
        ]);
    }

    /**
     * @param string|Plugin $plugin
     * @throws PluginRegistrationException
     */
    public function register($plugin)
    {
        $op = $plugin;
        if (is_string($plugin)) {
            $plugin = new $plugin();
        }

        $className = get_class($plugin);
        $container = $this->getContainer($className);

        if (empty($container)) {
            throw new PluginRegistrationException("Unable to register plugin: {$className}. Does not extend a plugin class.");
        }

        $this->plugins[$container][$className] = $plugin;
    }

    /**
     * @param Plugin $plugin
     * @return bool
     */
    public function isRegistered(Plugin $plugin)
    {
        $className = get_class($plugin);
        $container = $this->getContainer($className);

        if (empty($container)) {
            return false;
        }

        return isset($this->plugins[$container][$className]);
    }

    /**
     * @param string $className
     * @return string
     */
    private function getContainer(string $className): string
    {
        if (is_subclass_of($className, DiscountPlugin::class)) {
            $container = self::TYPE_DISCOUNT;
        } elseif (is_subclass_of($className, PaymentPlugin::class)) {
            $container = self::TYPE_PAYMENT;
        } elseif (is_subclass_of($className, PricePlugin::class)) {
            $container = self::TYPE_PRICE;
        } elseif (is_subclass_of($className, ShippingPlugin::class)) {
            $container = self::TYPE_SHIPPING;
        } elseif (is_subclass_of($className, TaxPlugin::class)) {
            $container = self::TYPE_TAX;
        } elseif (is_subclass_of($className, NotificationPlugin::class)) {
            $container = self::TYPE_NOTIFICATION;
        } else {
            $container = '';
        }

        return $container;
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->plugins->flatten()->toArray();
    }

    /**
     * @param string $type
     * @param null $className
     * @return array|Collection
     */
    public function getByType(string $type, $className = null)
    {
        $pluginsOfType = $this->plugins[$type] ?? [];

        if (count($pluginsOfType) <= 0) {
            return collect();
        }

        if (is_null($className)) {
            return $pluginsOfType;
        }

        return $pluginsOfType[$className] ?? collect();
    }

    public function __call($name, $arguments)
    {
        // Handle dynamic get() calls
        if (str_starts_with($name, 'get')) {
            $type = strtolower(substr($name, 3));

            return $this->getByType($type, $arguments[0] ?? null);
        }

        trigger_error("Call to unhandled __call function $name()", E_USER_ERROR);
    }
}
