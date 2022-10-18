<?php

namespace Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;

class PortalModel extends Model
{
    //use SoftDeletes;
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
    protected $fillable = [
        'id', 'franchise', 'type', 'name', 'shipping_locations', 'sector', 'address', 'phone', 'email', 'web', 'img', 'published', 'notes', 'data', 'web',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];


}
