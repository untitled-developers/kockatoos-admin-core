<?php

namespace UntitledDevelopers\KockatoosAdminCore\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;
use UntitledDevelopers\KockatoosAdminCore\Models\Admin;

class MfaService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function toggle(Admin $user): bool
    {
        if ($user->mfa_secret) {
            $user->mfa_secret = null;
            $user->save();
            return false;
        } else {
            $secret = $this->generateSecret();
            $user->mfa_secret = encrypt($secret);
            $user->save();
            return true;
        }
    }


    public function hasMfa(Admin $user): bool
    {
        return !is_null($user->mfa_secret);
    }


    /**
     * Get QR code URL for the user's MFA setup
     *
     * @param Admin $user
     * @return string|null QR code URL or null if MFA not enabled
     */
    public function getQrCodeUrl(Admin $user): ?string
    {
        if (!$user->mfa_secret) {
            return null;
        }

        $secret = decrypt($user->mfa_secret);
        $appName = config('app.name', 'Kockatoos Admin');

        return $this->google2fa->getQRCodeUrl(
            $appName,
            $user->username ?? $user->name,
            $secret
        );
    }

    /**
     * Get QR code image as SVG string
     *
     * @param Admin $user
     * @return string|null SVG markup or null if MFA not enabled
     */
    public function getQrCodeImage(Admin $user): ?string
    {
        $qrCodeUrl = $this->getQrCodeUrl($user);

        if (!$qrCodeUrl) {
            return null;
        }

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(300),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($qrCodeUrl);
    }

    public function verifyCode(Admin $user, string $code): bool
    {
        if (!$user->mfa_secret) {
            return false;
        }

        $secret = decrypt($user->mfa_secret);
        return $this->google2fa->verifyKey($secret, $code);
    }

    protected function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }
}
