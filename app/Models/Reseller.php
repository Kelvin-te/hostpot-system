<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    protected $fillable = [
        'business_name',
        'contact_name',
        'phone',
        'email',
        'brand_name',
        'logo_path',
        'mpesa_credentials',
        'status',
    ];

    protected $casts = [
        'mpesa_credentials' => 'encrypted:array',
    ];

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
