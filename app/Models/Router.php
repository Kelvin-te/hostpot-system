<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'ip', 'username', 'password'];

    public function packages() {
        return $this->hasMany(Package::class);
    }

}
