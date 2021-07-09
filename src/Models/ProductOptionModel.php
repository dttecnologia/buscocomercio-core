<?php

namespace  Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;

class ProductOptionModel extends Model
{

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_options';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product', 'var', 'value',
    ];
}
