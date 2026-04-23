<?php

namespace App\Services;

use App\Models\OtpToken;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generer(string $telephone, string $type, string $email): OtpToken
    {
        OtpToken::where('telephone', $telephone)
            ->where('type', $type)
            ->whereNull('used_at')
            ->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpToken::create([
            'telephone'        => $telephone,
            'code'             => $code,
            'type'             => $type,
            'expires_at'       => now()->addMinutes(10),
            'tentatives_echec' => 0,
            'created_at'       => now(),
        ]);

        $this->envoyer($email, $code);

        return $otp;
    }

    public function verifier(string $telephone, string $code, string $type): bool
    {
        $otp = OtpToken::where('telephone', $telephone)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('tentatives_echec', '<', 5)
            ->latest('created_at')
            ->first();

        if (!$otp) {
            return false;
        }

        if ($otp->code !== $code) {
            $otp->increment('tentatives_echec');
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }

    private function envoyer(string $email, string $code): void
    {
        Mail::raw(
            "Votre code de vérification CouturePro est : {$code}\n\nCe code expire dans 10 minutes. Ne le partagez jamais.",
            function ($message) use ($email) {
                $message->to($email)
                        ->subject('Votre code de vérification CouturePro');
            }
        );
    }
}
