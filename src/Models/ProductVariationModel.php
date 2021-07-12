<?php

namespace  Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;

class ProductVariationModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variations';

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
        'product', 'variation', 'cost_price', 'recommended_price', 'price', 'related_image', 'ean', 'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product', 'id');
    }


    /**
     * Get variations as array.
     *
     * @param  string  $value
     * @return string
     */
    public function getVariationAttribute($value)
    {
        return json_decode($value);
    }

        /**
     * Get variations full name (product + variation).
     *
     * @param  string  $value
     * @return string
     */
    public function getProductFullNameAttribute()
    {
        return $this->product()->first()->name . ' (' . $this->name . ')';
    }

    
    public function encodeVariations($variations)
    {
        $variationsArray = explode(",", $variations); // [key:value, key:value]
        foreach ($variationsArray as $variation) {
            $pos = strpos($variation, ":");
            $key = trim(substr($variation, 0, $pos));
            $value = trim(substr($variation, $pos + 1));
            $result[$key] = $value;
        }
        $this->variation = json_encode($result);
    }
}
