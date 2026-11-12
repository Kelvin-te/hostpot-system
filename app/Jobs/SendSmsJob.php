<?php

namespace App\Jobs;

use App\Services\VintexSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(VintexSmsService $sms): void
    {
        $result = $sms->sendSms($this->phone, $this->message);

        if (($result['success'] ?? false) !== true) {
            Log::warning('Queued SMS failed', [
                'phone' => $this->phone,
                'result' => $result,
            ]);
        }
    }
}
