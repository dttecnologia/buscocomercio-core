<?php

namespace Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;

class PortalModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'buscocomercio_portal';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
}
