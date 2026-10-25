<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'company_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'mpesa_receipt_number',
        'mpesa_phone_number',
        'status',
        'payment_date',
        'expires_at',
        'months_paid',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted($transactionReference = null, $receiptNumber = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'payment_date' => now(),
            'transaction_reference' => $transactionReference ?? $this->transaction_reference,
            'mpesa_receipt_number' => $receiptNumber ?? $this->mpesa_receipt_number,
        ]);
    }

    public function markAsFailed($reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'notes' => $reason ?? $this->notes,
        ]);
    }

    public function applyToSubscription(): bool
    {
        if (!$this->isCompleted()) {
            return false;
        }

        $subscription = $this->subscription;
        if (!$subscription) {
            return false;
        }

        // Calculate new expiry date
        $currentExpiry = $subscription->ends_at;
        if ($currentExpiry < now()) {
            // Subscription already expired, start from now
            $newExpiry = now()->addMonths($this->months_paid);
        } else {
            // Extend from current expiry
            $newExpiry = $currentExpiry->addMonths($this->months_paid);
        }

        // Update subscription
        $subscription->update([
            'ends_at' => $newExpiry,
            'status' => 'active',
            'last_payment_at' => now(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->transaction_reference,
        ]);

        // Update payment record
        $this->update(['expires_at' => $newExpiry]);

        return true;
    }
}
