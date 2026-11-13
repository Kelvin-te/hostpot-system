<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * When a reseller staff member is logged in, restrict queries on models
 * that have a reseller_id column to their own records only.
 * No-op for admin staff, subscribers, and unauthenticated requests.
 */
class ResellerScope implements Scope
{
    protected static ?int $resellerId = null;
    protected static array $columnCache = [];

    public static function set(?int $resellerId): void
    {
        static::$resellerId = $resellerId;
    }

    public static function resellerId(): ?int
    {
        return static::$resellerId;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $resellerId = static::currentResellerId();

        if ($resellerId === null) {
            return;
        }

        $table = $model->getTable();

        if (!array_key_exists($table, static::$columnCache)) {
            static::$columnCache[$table] = Schema::hasColumn($table, 'reseller_id');
        }

        if (static::$columnCache[$table]) {
            $builder->where($table . '.reseller_id', $resellerId);
        }
    }

    /**
     * Resolve the active reseller per query. Prefers the authenticated
     * staff user so Livewire/AJAX requests (which bypass route
     * middleware) still scope correctly; falls back to the static
     * override for console/queue contexts.
     *
     * The $resolving flag prevents infinite recursion: resolving the
     * staff user itself runs a Staff query, which re-enters apply().
     */
    protected static bool $resolving = false;

    protected static function currentResellerId(): ?int
    {
        // Console/queue workers have no session auth — use the static
        // override set by middleware/commands.
        $inConsole = app()->runningInConsole();

        if (static::$resolving) {
            // Nested resolution (the staff-auth query itself) must never
            // be reseller-scoped in HTTP context, or login breaks.
            return $inConsole ? static::$resellerId : null;
        }

        if ($inConsole) {
            return static::$resellerId;
        }

        static::$resolving = true;

        try {
            $staff = auth('staff')->user();
            if ($staff) {
                return $staff->isReseller() ? $staff->reseller_id : null;
            }
        } catch (\Throwable) {
            // Auth not available — treat as unscoped.
        } finally {
            static::$resolving = false;
        }

        return null;
    }
}
