<?php

namespace Animelhd\AnimesView\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $viewers
 * @property \Illuminate\Database\Eloquent\Collection $views
 */
trait Vieweable
{
    /**
     * @deprecated renamed to `hasBeenViewedBy`, will be removed at 5.0
     */
    public function isViewedBy(Model $user)
    {
        return $this->hasBeenViewedBy($user);
    }

    public function hasViewer(Model $user): bool
    {
        return $this->hasBeenViewedBy($user);
    }

    public function hasBeenViewedBy(Model $user): bool
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('viewers')) {
                return $this->viewers->contains($user);
            }

            return ($this->relationLoaded('views') ? $this->views : $this->views())
                    ->where(\config('animesview.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('animesview.view_model'), 'vieweable');
    }

    public function viewers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('animesview.views_table'),
            'vieweable_id',
            config('animesview.user_foreign_key')
        )
            ->where('vieweable_type', $this->getMorphClass());
    }
}
