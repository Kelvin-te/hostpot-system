<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiringSoon;
use App\Notifications\SubscriptionExpiringSms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';
    protected $description = 'Check for expiring subscriptions and send notifications';

    public function handle()
    {
        $this->info('Checking subscription expiry...');

        // Check subscriptions expiring in 7 days
        $expiringIn7Days = Subscription::expiringSoon(7)->get();
        $this->processExpiringSubscriptions($expiringIn7Days, 7);

        // Check subscriptions expiring in 3 days
        $expiringIn3Days = Subscription::expiringSoon(3)->get();
        $this->processExpiringSubscriptions($expiringIn3Days, 3);

        // Check subscriptions expiring in 1 day
        $expiringIn1Day = Subscription::expiringSoon(1)->get();
        $this->processExpiringSubscriptions($expiringIn1Day, 1);

        // Check expired subscriptions
        $expired = Subscription::expired()->where('status', '!=', 'expired')->get();
        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'expired']);
            $this->info("Marked subscription {$subscription->id} as expired");
        }

        $this->info('Subscription expiry check completed.');
    }

    protected function processExpiringSubscriptions($subscriptions, $daysRemaining)
    {
        foreach ($subscriptions as $subscription) {
            $company = $subscription->company;

            if (!$company || !$company->email) {
                $this->warn("Skipping subscription {$subscription->id} - no company email");
                continue;
            }

            try {
                // Send email notification
                Notification::route('mail', $company->email)
                    ->notify(new SubscriptionExpiringSoon($subscription, $daysRemaining));

                // Send SMS notification
                if ($company->phone) {
                    Notification::route('custom', $company->phone)
                        ->notify(new SubscriptionExpiringSms($subscription, $daysRemaining));
                }

                $this->info("Sent expiry notification to {$company->name} ({$daysRemaining} days remaining)");
            } catch (\Exception $e) {
                $this->error("Failed to send notification to {$company->name}: {$e->getMessage()}");
            }
        }
    }
}
