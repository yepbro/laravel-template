<?php

declare(strict_types=1);

namespace App\Auth\TwoFactor;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Project-owned TOTP management service.
 *
 * Uses pragmarx/google2fa and bacon/bacon-qr-code for secret generation,
 * code verification, and QR code rendering.
 */
class TwoFactorManager
{
    private readonly Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new base32 TOTP secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Verify a TOTP code against the given plaintext secret.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Build the otpauth:// URL for authenticator apps.
     */
    public function qrCodeUrl(string $appName, string $userIdentifier, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($appName, $userIdentifier, $secret);
    }

    /**
     * Render an SVG QR code for the given credentials.
     *
     * Returns the SVG markup without the XML declaration header line.
     */
    public function qrCodeSvg(string $appName, string $userIdentifier, string $secret): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(
                    192,
                    0,
                    null,
                    null,
                    Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72)),
                ),
                new SvgImageBackEnd(),
            ),
        );

        $svg = $writer->writeString($this->qrCodeUrl($appName, $userIdentifier, $secret));

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
