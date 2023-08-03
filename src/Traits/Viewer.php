<?php

namespace Animelhd\AnimesView\Traits;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * @property \Illuminate\Database\Eloquent\Collection $views
 */
trait Viewer
{
    public function view(Model $object): void
    {
        /* @var \Animelhd\AnimesView\Traits\Vieweable|Model $object */
        if (! $this->hasViewed($object)) {
            $view = app(config('animesview.view_model'));
            $view->{config('animesview.user_foreign_key')} = $this->getKey();

            $object->views()->save($view);
        }
    }

    public function unview(Model $object): void
    {
        /* @var \Animelhd\AnimesView\Traits\Vieweable $object */
        $relation = $object->views()
            ->where('vieweable_id', $object->getKey())
            ->where('vieweable_type', $object->getMorphClass())
            ->where(config('animesview.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    public function toggleView(Model $object): void
    {
        $this->hasViewed($object) ? $this->unview($object) : $this->view($object);
    }

    public function hasViewed(Model $object): bool
    {
        return ($this->relationLoaded('views') ? $this->views : $this->views())
            ->where('vieweable_id', $object->getKey())
            ->where('vieweable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('animesview.view_model'), config('animesview.user_foreign_key'), $this->getKeyName());
    }

    public function attachViewStatus(&$vieweables, callable $resolver = null)
    {
        $views = $this->views()->get()->keyBy(function ($item) {
            return \sprintf('%s-%s', $item->vieweable_type, $item->vieweable_id);
        });

        $attachStatus = function ($vieweable) use ($views, $resolver) {
            $resolver = $resolver ?? fn ($m) => $m;
            $vieweable = $resolver($vieweable);

            if (\in_array(Vieweable::class, \class_uses($vieweable))) {
                $key = \sprintf('%s-%s', $vieweable->getMorphClass(), $vieweable->getKey());
                $vieweable->setAttribute('has_viewed', $views->has($key));
            }

            return $vieweable;
        };

        switch (true) {
            case $vieweables instanceof Model:
                return $attachStatus($vieweables);
            case $vieweables instanceof Collection:
                return $vieweables->each($attachStatus);
            case $vieweables instanceof LazyCollection:
                return $vieweables = $vieweables->map($attachStatus);
            case $vieweables instanceof AbstractPaginator:
            case $vieweables instanceof AbstractCursorPaginator:
                return $vieweables->through($attachStatus);
            case $vieweables instanceof Paginator:
                // custom paginator will return a collection
                return collect($vieweables->items())->transform($attachStatus);
            case \is_array($vieweables):
                return \collect($vieweables)->transform($attachStatus);
            default:
                throw new \InvalidArgumentException('Invalid argument type.');
        }
    }

    public function getViewItems(string $model)
    {
        return app($model)->whereHas(
            'viewers',
            function ($q) {
                return $q->where(config('animesview.user_foreign_key'), $this->getKey());
            }
        );
    }
}