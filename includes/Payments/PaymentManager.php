<?php

namespace MyBooking\Payments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PaymentManager
 * 
 * Factory for switching between different payment gateways.
 */
class PaymentManager
{
    /** @var array Keep track of initialized drivers */
    private static $drivers = [];

    /**
     * Get a gateway driver instance.
     *
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws \Exception
     */
    public static function get_driver(string $gateway = 'stripe'): PaymentGatewayInterface
    {
        if (isset(self::$drivers[$gateway])) {
            return self::$drivers[$gateway];
        }

        switch ($gateway) {
            case 'stripe':
                self::$drivers[$gateway] = new StripeDriver();
                break;
            default:
                throw new \Exception("Payment gateway '{$gateway}' not supported.");
        }

        return self::$drivers[$gateway];
    }
}
