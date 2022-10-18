<?php

namespace  Buscocomercio\Core;

use Buscocomercio\Core\ProductModel;
use Buscocomercio\Core\ProviderModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductProviderModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_provider';

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
        'product', 'variation', 'ean', 'reference', 'cost_price', 'provider', 'stock',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     *  Relationship brand hasOne
     */
    public function __product()
    {
        return $this->hasOne(ProductModel::class, 'id', 'product');
    }

    /**
     *  Relationship brand hasOne
     */
    public function __provider()
    {
        return $this->hasOne(ProviderModel::class, 'id', 'provider');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::created(function ($productProvider) {
            $product = ProductModel::find($productProvider->product);
            $productVariation = ProductVariationModel::find($productProvider->variation);
            if ($productVariation){
                $variation = $productVariation->variation;
            }
            else{
                $variation = null;
            }
            if (!$product->franchise){
                $product->updatePrice($variation);
            }
        });

        self::updated(function ($productProvider) {
            $product = ProductModel::find($productProvider->product);
            $productVariation = ProductVariationModel::find($productProvider->variation);
            if ($productVariation){
                $variation = $productVariation->variation;
            }
            else{
                $variation = null;
            }
            if (!$product->franchise){
                $product->updatePrice($variation);
            }
        });
    }

    /**
     * Obtiene el proveedor
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function getProvider()
    {
        return ProviderModel::find($this->provider);
    }
}
