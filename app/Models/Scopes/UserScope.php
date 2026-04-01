<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (auth()->check()) {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Only apply if we have a logged-in user and they aren't admin
            if ($user && $user->role !== 'admin') {
                $builder->where($model->getTable() . '.user_id', $user->id);
            }
        }
    }
}
