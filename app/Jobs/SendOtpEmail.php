<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private string $email,
        private string $code
    ) {}

    public function handle(): void
    {
        Mail::raw(
            "Votre code de vérification Gextimo est : {$this->code}\n\nCe code expire dans 10 minutes. Ne le partagez jamais.",
            function ($message) {
                $message->to($this->email)
                        ->subject('Votre code de vérification Gextimo');
            }
        );
    }
}
