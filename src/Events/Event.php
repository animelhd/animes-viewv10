<?php

namespace Animelhd\AnimesView\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    public Model $view;

    public function __construct(Model $view)
    {
        $this->view = $view;
    }
}
