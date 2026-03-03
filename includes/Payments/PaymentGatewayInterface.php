<?php

namespace MyBooking\Payments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface PaymentGatewayInterface
 * 
 * Defines the contract for all payment gateways in the Booking Plugin.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment intent (e.g., Stripe PaymentIntent).
     *
     * @param int $booking_id The ID of the booking.
     * @param array $args Additional arguments (amount, currency, etc.).
     * @return array|object The gateway-specific intent data.
     */
    public function create_intent(int $booking_id, array $args): array |object;

    /**
     * Verify a webhook request.
     *
     * @param string $payload The raw request body.
     * @param string $signature_header The signature header from the request.
     * @return bool|object The verified event object or false on failure.
     */
    public function verify_webhook(string $payload, string $signature_header): bool|object;

    /**
     * Handle a refund request.
     *
     * @param string $transaction_id The gateway transaction ID.
     * @param float $amount The amount to refund.
     * @return bool Success status.
     */
    public function handle_refund(string $transaction_id, float $amount): bool;
}
