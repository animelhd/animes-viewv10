<?php

namespace Animelhd\AnimesView;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Animelhd\AnimesView\Events\Viewed;
use Animelhd\AnimesView\Events\Unviewed;

/**
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $viewer
 * @property \Illuminate\Database\Eloquent\Model $vieweable
 */
class View extends Model
{
    protected $guarded = [];

    protected $dispatchesEvents = [
        'created' => Viewed::class,
        'deleted' => Unviewed::class,
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = \config('animesview.views_table');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($view) {
            $userForeignKey = \config('animesview.user_foreign_key');
            $view->{$userForeignKey} = $view->{$userForeignKey} ?: auth()->id();

            if (\config('animesview.uuids')) {
                $view->{$view->getKeyName()} = $view->{$view->getKeyName()} ?: (string) Str::orderedUuid();
            }
        });
    }

    public function vieweable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('animesview.user_foreign_key'));
    }

    public function viewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->user();
    }

    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('vieweable_type', app($type)->getMorphClass());
    }
}
