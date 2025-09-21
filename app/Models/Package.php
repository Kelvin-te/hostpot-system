<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Package
 *
 * @mixin Eloquent
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'price', 
        'router_id',
        'bandwidth_upload',
        'bandwidth_download', 
        'session_timeout',
        'idle_timeout',
        'shared_users',
        'rate_limit',
        'validity_days'
    ];

    public function router() {
        return $this->belongsTo(Router::class);
    }
}
