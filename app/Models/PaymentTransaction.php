<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'merchant_request_id',
        'phone_number',
        'amount',
        'account_reference',
        'transaction_desc',
        'status',
        'mpesa_receipt_number',
        'transaction_date',
        'response_code',
        'response_description',
        'customer_message',
        'result_code',
        'result_description',
        'callback_data',
        'package_id',
        'user_id',
        'session_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'callback_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the package associated with this transaction
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user associated with this transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hotspot session associated with this transaction
     */
    public function session()
    {
        return $this->belongsTo(HotspotSession::class);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is expired (older than timeout period)
     */
    public function isExpired(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $timeout = config('mpesa.transaction_timeout', 300); // 5 minutes default
        return $this->created_at->addSeconds($timeout)->isPast();
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $data = []): bool
    {
        return $this->update(array_merge([
            'status' => 'completed',
            'result_code' => 0
        ], $data));
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null, int $resultCode = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'result_code' => $resultCode,
            'result_description' => $reason
        ]);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for transactions within timeout period
     */
    public function scopeNotExpired($query)
    {
        $timeout = config('mpesa.transaction_timeout', 300);
        return $query->where('created_at', '>', Carbon::now()->subSeconds($timeout));
    }
}
