<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BelongsToCompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Only apply scope if user is authenticated and has a company_id
        if (Auth::check() && Auth::user()->company_id) {
            $builder->where('company_id', Auth::user()->company_id);
        }
    }
}
