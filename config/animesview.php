<?php

return [
    /**
     * Use uuid as primary key.
     */
    'uuids' => false,

    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /*
     * Table name for views records.
     */
    'views_table' => 'views',

    /*
     * Model name for view record.
     */
    'view_model' => Animelhd\AnimesView\View::class,
];