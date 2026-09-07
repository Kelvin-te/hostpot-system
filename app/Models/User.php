<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'router_id',
        'mac_address',
        'total_spent',
        'total_sessions',
        'total_data_used',
        'last_session_at',
        'wallet_balance',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_session_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    public function isAdmin(): bool
    {
        return false;
    }

    public function isUser(): bool
    {
        return true;
    }

    public function due_amount($id){
        $user = self::where('id', $id)->firstOrFail();

        $totalBilled = PaymentTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $totalPaid = PaymentTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        return $totalBilled - $totalPaid;
    }

    public function detail() {
        return $this->hasOne(Detail::class);
    }

    public function billing() {
        return $this->hasMany(Billing::class);
    }

    public function payment() {
        return $this->hasMany(Payment::class);
    }

    public function transactions() {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function sessions() {
        return $this->hasMany(HotspotSession::class);
    }

    public function router() {
        return $this->belongsTo(Router::class);
    }

    public function walletTransactions() {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getActiveSessionAttribute(): ?HotspotSession
    {
        return $this->sessions()->active()->latest('created_at')->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeBanned($query)
    {
        return $query->where('status', 'banned');
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }
}
