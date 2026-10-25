<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringSoon extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $company = $this->subscription->company;
        $planName = $this->subscription->getPlanName();
        $expiryDate = $this->subscription->ends_at->format('F j, Y');

        return (new MailMessage)
            ->subject("Subscription Expiring in {$this->daysRemaining} Days - {$company->name}")
            ->greeting("Hello {$company->name},")
            ->line("Your {$planName} subscription will expire in {$this->daysRemaining} days on {$expiryDate}.")
            ->line("To avoid service interruption, please renew your subscription before the expiry date.")
            ->action('Renew Subscription', route('companies.show', $company->id))
            ->line("If you have any questions, please contact our support team.")
            ->line("Thank you for using our service!");
    }

    public function toDatabase($notifiable)
    {
        return [
            'subscription_id' => $this->subscription->id,
            'company_id' => $this->subscription->company_id,
            'days_remaining' => $this->daysRemaining,
            'expiry_date' => $this->subscription->ends_at,
            'message' => "Your subscription expires in {$this->daysRemaining} days",
        ];
    }
}
