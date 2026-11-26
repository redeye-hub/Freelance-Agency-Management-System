<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * A fake payment gateway for the university project. NO real money moves.
 *
 * It accepts exactly two published TEST card numbers and refuses everything else, so a real card
 * number typed by mistake is rejected and never processed or stored.
 *
 * To go live later, replace charge() with a call to a real provider (for example the SSLCommerz or
 * Stripe sandbox SDK) and keep the same return shape.
 */
class SandboxGateway
{
    public const SUCCESS_CARD = '4242424242424242';
    public const DECLINE_CARD = '4000000000000002';

    /**
     * @return array{ok: bool, reference?: string, last4?: string, message?: string}
     */
    public function charge(string $cardNumber, float $amount): array
    {
        $card = preg_replace('/\D/', '', $cardNumber);

        if ($card === self::SUCCESS_CARD) {
            return ['ok' => true, 'reference' => 'sbx_' . Str::lower(Str::random(14)), 'last4' => '4242'];
        }

        if ($card === self::DECLINE_CARD) {
            return ['ok' => false, 'message' => 'The sandbox test card was declined.'];
        }

        return ['ok' => false, 'message' => 'Sandbox mode only accepts the test cards listed on this page. Do not enter a real card.'];
    }
}
