<?php

namespace MyBooking\Payments;

use BookingApp\Settings;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StripeDriver
 * 
 * Implementation of the PaymentGatewayInterface for Stripe.
 */
class StripeDriver implements PaymentGatewayInterface
{
    /** @var StripeClient */
    private $client;

    /** @var array Gateway settings */
    private $settings;

    public function __construct()
    {
        $this->settings = Settings::instance()->get_options();
        $secret_key = $this->get_secret_key();

        if (!empty($secret_key)) {
            $this->client = new StripeClient($secret_key);
        }
    }

    /**
     * Get the decrypted secret key.
     */
    private function get_secret_key(): string
    {
        $encrypted_key = $this->settings['payments']['stripe']['secret'] ?? '';
        return PaymentsSecurity::decrypt($encrypted_key);
    }

    /**
     * Get the publishable key.
     */
    public function get_publishable_key(): string
    {
        return $this->settings['payments']['stripe']['publishable'] ?? '';
    }

    /**
     * Get the webhook secret.
     */
    private function get_webhook_secret(): string
    {
        $encrypted_secret = $this->settings['payments']['stripe']['webhook_secret'] ?? '';
        return PaymentsSecurity::decrypt($encrypted_secret);
    }

    /**
     * Create a Stripe PaymentIntent.
     *
     * @param int $booking_id The ID of the booking.
     * @param array $args Additional arguments (amount, currency, etc.).
     * @return array The intent client secret and other data.
     */
    public function create_intent(int $booking_id, array $args): array
    {
        if (!$this->client) {
            throw new \Exception("Stripe client not initialized. Check API keys.");
        }

        try {
            $intent = $this->client->paymentIntents->create([
                'amount' => round($args['amount'] * 100), // Stripe expects cents
                'currency' => strtolower($args['currency'] ?? 'usd'),
                'metadata' => [
                    'booking_id' => $booking_id,
                ],
            ], [
                // Idempotency key to prevent double charging
                'idempotency_key' => 'booking_' . $booking_id . '_' . time(),
            ]);

            $this->log("PaymentIntent created for booking #{$booking_id}: {$intent->id}");

            return [
                'id' => $intent->id,
                'client_secret' => $intent->client_secret,
            ];
        }
        catch (\Exception $e) {
            $this->log("Error creating PaymentIntent for booking #{$booking_id}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Verify a Stripe Webhook request.
     */
    public function verify_webhook(string $payload, string $signature_header): bool|object
    {
        $secret = $this->get_webhook_secret();

        try {
            $event = Webhook::constructEvent($payload, $signature_header, $secret);
            return $event;
        }
        catch (SignatureVerificationException $e) {
            $this->log("Webhook signature verification failed: " . $e->getMessage(), 'error');
            return false;
        }
        catch (\Exception $e) {
            $this->log("Webhook verification error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Handle a refund via Stripe.
     */
    public function handle_refund(string $transaction_id, float $amount): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $this->client->refunds->create([
                'payment_intent' => $transaction_id,
                'amount' => round($amount * 100),
            ]);
            $this->log("Refund issued for transaction #{$transaction_id}: {$amount}");
            return true;
        }
        catch (\Exception $e) {
            $this->log("Error processing refund for #{$transaction_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Log messages to a dedicated payments log file.
     */
    public function log(string $message, string $level = 'info'): void
    {
        $log_dir = BOOKING_APP_PATH . 'logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $log_file = $log_dir . '/payments.log';
        $timestamp = date('Y-m-d H:i:s');
        $formatted_msg = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        file_put_contents($log_file, $formatted_msg, FILE_APPEND);
    }
}
