<?php

namespace App\Notifications;

use App\Services\VintexSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringSms extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;
    protected $daysRemaining;

    public function __construct($subscription, $daysRemaining)
    {
        $this->subscription = $subscription;
        $this->daysRemaining = $daysRemaining;
    }

    public function via($notifiable)
    {
        return ['custom'];
    }

    public function toCustom($notifiable)
    {
        $company = $this->subscription->company;
        $planName = $this->subscription->getPlanName();
        $expiryDate = $this->subscription->ends_at->format('F j, Y');

        $message = "Hello {$company->name}, your {$planName} subscription expires in {$this->daysRemaining} days on {$expiryDate}. Please renew to avoid service interruption. Thank you.";

        // Send SMS using VintexSmsService
        if ($company->phone) {
            try {
                $smsService = new VintexSmsService();
                $smsService->sendSms($company->phone, $message);
            } catch (\Exception $e) {
                \Log::error('Failed to send subscription expiry SMS', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [];
    }
}
