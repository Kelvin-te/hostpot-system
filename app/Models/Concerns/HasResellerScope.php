<?php

namespace App\Models\Concerns;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Scopes\ResellerScope;
use Illuminate\Support\Facades\Schema;

trait HasResellerScope
{
    protected static function bootHasResellerScope(): void
    {
        static::addGlobalScope(new ResellerScope);

        // Stamp reseller_id on new rows so scoped reads stay consistent.
        // Derives from the owning package/user/router when possible,
        // falling back to the active reseller staff context.
        static::creating(function ($model) {
            if (!Schema::hasColumn($model->getTable(), 'reseller_id') || $model->reseller_id) {
                return;
            }

            $model->reseller_id = static::resolveResellerId($model) ?? ResellerScope::resellerId();
        });
    }

    protected static function resolveResellerId($model): ?int
    {
        if (!empty($model->package_id) && Schema::hasColumn('packages', 'reseller_id')) {
            return Package::withoutGlobalScopes()->whereKey($model->package_id)->value('reseller_id');
        }

        if (!empty($model->user_id)) {
            return User::withoutGlobalScopes()->whereKey($model->user_id)->value('reseller_id');
        }

        if (!empty($model->router_id)) {
            return Router::withoutGlobalScopes()->whereKey($model->router_id)->value('reseller_id');
        }

        return null;
    }
}
