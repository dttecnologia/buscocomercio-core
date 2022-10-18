<?php

namespace Buscocomercio\Core;

use App\Product;
use Carbon\Carbon;
use Buscocomercio\Core\TaxModel;
use Buscocomercio\Core\BrandModel;
use Buscocomercio\Core\ProductCustom;
use Buscocomercio\Core\ProviderModel;
use Illuminate\Support\Facades\DB;
use Buscocomercio\Core\FranchiseModel;
use Buscocomercio\Core\OrderDetailModel;
use Buscocomercio\Core\ProductStockModel;
use Buscocomercio\Core\ProductCustomModel;
use Illuminate\Database\Eloquent\Model;
use Buscocomercio\Core\DiscountTargetsModel;
use Buscocomercio\Core\FranchiseCustomModel;
use Buscocomercio\Core\ProductCategoryModel;
use Buscocomercio\Core\ProductProviderModel;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Services\SlugService;

class ProductModel extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product';

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
        'slug', 'name', 'description', 'stock_type', 'minimum_stock', 'transport', 'weight', 'volume', 'tax', 'brand', 'category', 'tags', 'variations', 'franchise', 'promotion', 'free_shipping', 'double_unit', 'units_limit', 'liquidation', 'unavailable', 'discontinued', 'external_sale', 'highlight', 'price_edit', 'shipping_canarias', 'cost_price', 'recommended_price', 'default_price', 'profit_margin', 'franchise_profit_margin', 'price_rules', 'meta_title', 'meta_description', 'meta_keywords', 'net_quantity', 'unit', 'stock', 'provider', 'ean', 'provider_ref'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'unique' => true,
                'onUpdate' => true,
                'maxLength' => 191,
            ]
        ];
    }
    
    public function scopeWithUniqueSlugConstraints(Builder $query, Model $model, $attribute, $config, $slug) {
        return $query->where('franchise', FranchiseModel::getFranchise()->id);
    }
    /**
     *  Relationship brand hasOne
     */
    public function brands()
    {
        return $this->hasOne('Buscocomercio\Core\BrandModel', 'id', 'brand');
    }
    /**
     * Relationship product custom hasOne
     */
    public function productCustoms()
    {
        return $this->hasMany('Buscocomercio\Core\ProductCustomModel', 'product', 'id');
    }
    /**
     * Relationship product provider hasOne
     */
    public function productProvider()
    {
        return $this->hasMany('Buscocomercio\Core\ProductProviderModel', 'product', 'id');
    }

    /**
     *  Relationship brand hasOne
     */
    public function __provider()
    {
        return $this->belongsTo(ProviderModel::class, 'provider', 'id');
    }

    /**
     * Relationship product image hasOne
     */
    public function productImage()
    {
        return $this->hasMany('Buscocomercio\Core\ProductImageModel', 'product', 'id');
    }

    public function getUnitNameAttribute()
    {
        switch ($this->unit) {
            case 0:
                return 'KG';
                break;
            case 1:
                return 'litro';
                break;
            case 2:
                return 'unidad';
                break;
        }
    }
    /**
     * Relationship product image hasOne
     */
    public function productVariation()
    {
        return $this->hasMany('Buscocomercio\Core\ProductVariationModel', 'product', 'id');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     * @author Aaron <aaron@devuelving.com>
     */
    public static function boot()
    {
        parent::boot();

        self::updating(function ($product) {
            // We check if the product belongs to a franchise
            if ($product->franchise === NULL) {
                // We check if the status of the product has changed
                if ($product->isDirty('discontinued')) {
                    // To prevent both clutter in the .rss and the database, we will only use one register per product
                    $productStatusUpdate = ProductStatusUpdatesModel::where('product', $product->id)->first();
                    if (!$productStatusUpdate) {
                        $productStatusUpdate = new ProductStatusUpdatesModel();
                    }
                    $productStatusUpdate->product = $product->id;
                    // Depending on the change, we inform the user one way or another through the .rss feed
                    if ($product->discontinued > 0) {
                        $productStatusUpdate->status = "El producto ha sido descatalogado";
                    } else {
                        $productStatusUpdate->status = "Producto añadido al catalogo de nuevo";
                    }
                    $productStatusUpdate->save();
                    // We only let the users know that the status have changed if the product is not discontinued
                } else if ($product->isDirty('unavailable') && $product->discontinued == 0) {
                    // To prevent both clutter in the .rss and the database, we will only use one register per product
                    $productStatusUpdate = ProductStatusUpdatesModel::where('product', $product->id)->first();
                    if (!$productStatusUpdate) {
                        $productStatusUpdate = new ProductStatusUpdatesModel();
                    }
                    $productStatusUpdate->product = $product->id;
                    // Depending on the change, we inform the user one way or another through the .rss feed
                    if ($product->unavailable > 0) {
                        $productStatusUpdate->status = "Stock agotado";
                    } else {
                        $productStatusUpdate->status = "Producto en stock de nuevo";
                    }
                    $productStatusUpdate->save();
                }
            }
        });
        
        static::registerModelEvent('slugging', static function($model) {
            if(empty(request("id")) || !empty(request("id")) && request("update_slug") === "true") {
                info('Product slugging: ' . $model->id . ' -> ' . $model->name);
            } else {
                return false;
            }
        });
        
        static::registerModelEvent('slugged', static function($model) {
            //info('Category slugged: ' . $model->slug);
        });
    }

    /**
     * Actualiza los precios del producto en la tabla de productos
     *
     * @since 3.0.0
     * @author Aaron <aaron@devuelving.com>
     * @return void
     */
    public function updatePrice($variation = null)
    {
        if ($this->franchise === null) {
            // Obtenemos la regla del precio establecida
            if ($this->price_rules == 1) {
                $rule = 'asc';
            } else {
                $rule = 'desc';
            }
            // Obtenemos el product provider
            $productProvider = $this->getProductProvider(false, $variation);
            // Obtenemos el proveedor del producto
            $provider = $productProvider->getProvider(false, $variation);
            // Obtenemos el precio de coste y le sumamos el margen de beneficio del proveedor
            $costPrice = $productProvider->cost_price + ($productProvider->cost_price * ($provider->profit_margin / 100));
            if ($provider->id == 5 || $provider->id == 6) {
                // Obtenemos el precio recomendado y le restamos el 10% que es el precio minimo de venta
                $default_price = $this->getRecommendedPrice($variation) / 1.10;
            } else {
                // Obtenemos el precio de coste y le sumamos el beneficio del franquiciado por defecto
                $default_price = ($costPrice + ($productProvider->cost_price * ($provider->franchise_profit_margin / 100))) * ((TaxModel::find($this->tax)->value / 100) + 1);
            }
            // Actualizamos los precios de los productos
            if ($variation != null) {
                $product = ProductVariationModel::find($variation);
                $product->price = $default_price;
            } else {
                $product = ProductModel::find($this->id);
                $product->default_price = $default_price;
            }
            $oldCostPrice = $product->cost_price / (1 + $provider->profit_margin / 100);
            $product->cost_price = $costPrice;
            $newCostPrice = $product->cost_price / (1 + $provider->profit_margin / 100);
            if ($product->save()) {
                if ($variation == null) {
                    // Actualitzem els preus de totes les variacions menys les que ja tinguin un preu especific.
                    $productVariations = ProductVariationModel::where('product', $this->id)->get();
                    foreach ($productVariations as $productVariation) {
                        $productProvider = ProductProviderModel::where('variation', $productVariation->id)->first();
                        if (!$productProvider) {
                            $productVariation->cost_price = $costPrice;
                            $productVariation->price = $default_price;
                            $productVariation->save();
                            if (number_format($newCostPrice, 1) != number_format($oldCostPrice, 1)) {
                                $this->addUpdatePrice($newCostPrice, $oldCostPrice, $variation);
                            }
                        }
                    }
                    /* ProductVariationModel::where('product', $this->id)
                    ->whereNotNull('variation')
                    ->update(['cost_price' => $costPrice, 'price' => $default_price]); */
                }
            }
            // Comprobación de que el precio no es el mismo
            if (number_format($newCostPrice, 1) != number_format($oldCostPrice, 1)) {
                // Añadimos el registro de la nueva actualización del precio
                $this->addUpdatePrice($newCostPrice, $oldCostPrice, $variation);
            }
        }
    }

    /**
     * Añade una actualización del precio del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param float $costPrice
     * @param float $oldCostPrice
     * @return void
     */
    public function addUpdatePrice($costPrice = 0, $oldCostPrice = 0, $variation = null)
    {
        // Obtenemos el anterior precio del producto
        try {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            $oldType = $productPriceUpdate->type;
        } catch (\Exception $e) {
            // report($e);
            $oldType = 1;
        }
        // Obtenemos el tipo de actualización del precio del producto
        if ($oldCostPrice == 0 || ($oldType == 3 && ($this->unavailable == 0 || $this->discontinued == 0))) {
            $type = 1; // Nuevo producto
        } else if ($this->unavailable == 1 || $this->discontinued == 1) {
            $type = 3; // Eliminación producto
        } else {
            $type = 2; // Actualización del precio
        }
        // Comprobación de que el precio no es el mismo
        if (number_format($costPrice, 1) != number_format($oldCostPrice, 1)) {
            // Se añade un nuevo registro con el nuevo precio del producto
            DB::table('product_price_update')->insert([
                'product' => $this->id,
                'variation' => $variation,
                'type' => $type,
                'new_price_cost' => $costPrice,
                'old_price_cost' => $oldCostPrice,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Función para obtener los datos de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $data
     * @return void
     */
    public function getData($data)
    {
        return $this->$data;
    }

    /**
     * Función para obtener todas las imagenes de un producto ordenadas por preferencia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return array
     * @param $images ProductImageModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getImages($images = null, $redirect = true)
    {
        $return = [];
        if ($images == null)
            $images = DB::table('product_image')->where('product', $this->id)->orderBy('default', 'desc')->get();

        foreach ($images as $image) {
            if ($redirect) {
                $return[] = route('index') . '/cdn/' . $image->image;
            } else {
                $return[] = config('app.cdn.url') . $image->image;
            }
        }
        if (count($return) < 1) {
            if ($redirect) {
                $return[] = route('index') . '/cdn/product/default.png';
            } else {
                $return[] = config('app.cdn.url') . 'default.png';
            }
        }
        return $return;
    }

    /**
     * Función para obtener la imagen destacada
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDefaultImage($redirect = true)
    {
        return $this->getImages(null, $redirect)[0];
    }

    /**
     * Función para obtener los ean del producto
     * @return array
     * @param $productProviders ProductProviderModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getEan($productProviders = null)
    {
        $return = [];
        if ($productProviders == null)
            $productProviders = ProductProviderModel::where('product', $this->id)->get();

        foreach ($productProviders as $productProvider) {
            $return[] = $productProvider->ean;
        }
        return $return;
    }

    /**
     * Función para obtener el ean en un string
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function eanToString()
    {
        $return = '';
        foreach ($this->getEan() as $ean) {
            $return .= $ean . ' - ';
        }
        if (empty($return)) {
            $return = 'Sin EAN';
        } else {
            $return = substr($return, 0, -3);
        }
        return $return;
    }

    /**
     * Función para obtener la marca
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return BrandModel
     * @param $brand BrandModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getBrand($brand = null)
    {
        if ($brand == null)
            return BrandModel::find($this->brand);
        else
            return $brand;
    }

    /**
     * Función para obtener el valor del iva de este producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getTax()
    {
        $tax = TaxModel::find($this->tax);
        return $tax->value / 100;
    }

    /**
     * Returns Provider for the product
     *
     * @param boolean $cheapest
     * @return void
     */
    public function getProvider($cheapest = false, $variation = null)
    {
        $provider_product = ProviderModel::find($this->provider);
        return $provider_product;
        //return $this->getProductProvider($cheapest, $variation)->__provider;
    }

    /**
     * Función para obtene el producto proveedor
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $cheapest
     * @return void
     */
    public function getProductProvider($cheapest = false, $variation = null)
    {
        try {
            if ($this->franchise === null) {
                if (!$cheapest) {
                    if ($this->price_rules == 1) {
                        $rule = 'asc';
                    } else {
                        $rule = 'desc';
                    }
                } else {
                    $rule = 'asc';
                }
                $productProvider = ProductModel::join('provider', 'product.provider', '=', 'provider.id');
                $productProvider->where('product.id', $this->id);
                $productProvider->where('provider.active', 1);
                if ($variation != null && ProductModel::where('id', $this->id)->where('variations', $variation)->exists()) {
                    $productProvider->where('product.variations', $variation);
                } else {
                    //$productProvider->where('product_provider.variation', 0);
                    $productProvider->OrWhereNull('product.variations');
                }                
                
                //$productProvider->orderBy('product_provider.cost_price', $rule);
                $productProvider->select('product.*', 'provider.name');
                $productProvider = $productProvider->first();
            } else {
                if (!$cheapest) {
                    $rule = 'desc';
                } else {
                    $rule = 'asc';
                }
                $productProvider = ProductModel::where('id', $this->id);
                if ($variation != null && ProductModel::where('id', $this->id)->where('variations', $variation)->exists()) {
                    $productProvider->where('variations', $variation);
                } else {
                    $productProvider->whereNull('variations');
                }
                //$productProvider->orderBy('cost_price', $rule);
                $productProvider = $productProvider->first();
            }
            return $productProvider;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Función para obtener datos de producto proveedor
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $data
     * @param boolean $cheapest
     * @return void
     */
    public function getProductProviderData($data, $cheapest = false, $variation = null)
    {
        try {
            $productProvider = $this->getProductProvider($cheapest, $variation);
            return $productProvider->$data;
        } catch (\Exception $e) {
            // report($e);
            return null;
        }
    }

    /**
     * Función para obtener el precio de coste
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $tax
     * @return void
     */
    public function getPublicPriceCost($tax = true, $variation = null)
    {
        $discount = 1; //$this->getDiscountTarget(); 01/07/22 Siscu: cancelo esta llamada
        if ($variation == null) {
            $cost_price = $this->cost_price;
        } else {
            $cost_price = ProductVariationModel::find($variation)->cost_price;
        }
        if ($tax) {
            return ($cost_price * $discount) * ($this->getTax() + 1);
        } else {
            return ($cost_price * $discount);
        }
    }

    /**
     * Función para comprobar si tienen activos algún descuento y aplicarlo al precio de coste
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDiscountTarget()
    {
        $discount = 1;
        if (FranchiseModel::getFranchise()) {
            try {
                // Comprobamos si la franquicia tiene los descuentos activados
                if (FranchiseModel::getFranchise()->getCustom('discount') != null) {
                    $franchiseDiscounts = json_decode(FranchiseModel::getFranchise()->getCustom('discount'));
                    // Recorremos todos los descuentos de la franquicia
                    foreach ($franchiseDiscounts as $FranchiseDiscountTarget) {
                        // Obtenemos los datos de los descuentos
                        $discountTarget = DiscountTargetsModel::find($FranchiseDiscountTarget);
                        $target = json_decode($discountTarget->target);
                        // Comprobamos si el descuento es de tipo 1, lo que significa que el id del producto esta en los datos del descuento
                        if ($discountTarget->type == 1) {
                            if (in_array($this->id, $target)) {
                                $discount = 1 - ($discountTarget->discount / 100);
                            }
                            // Comprobamos si el descuento es de tipo 2, lo que significa que se aplica un descuento por proveedor
                        } else if ($discountTarget->type == 2) {
                            if (in_array($this->provider, $target)) {
                                $discount = 1 - ($discountTarget->discount / 100);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                report($e);
            }
        }
        return $discount;
    }

    /**
     * Función para obtener el precio de coste sin IVA
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPublicPriceCostWithoutIva($variation = null)
    {
        return $this->getPublicPriceCost(false, $variation);
    }

    /**
     * Función para obtener el precio sin IVA
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPriceWithoutIva($variation = null)
    {
        if ($this->getPrice($variation) != null) {
            return $this->getPrice($variation);
        }
        return null;
    }

    /**
     * Función para obtener el precio con el margen de beneficio del proveedor anterior al cambio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getOldPublicPriceCost($variation = null)
    {
        if ($variation == null) {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
        } else {
            $productPriceUpdate = DB::table('product_price_update')->where('variation', $variation)->orderBy('id', 'desc')->first();
        }
        return $productPriceUpdate->price + ($productPriceUpdate->price * $this->getTax());
    }

    /**
     * Función para obtener la fecha de la ultima actualización de los precios de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getLastPriceUpdate($variation = null)
    {
        if ($this->getProductProviderData('cost_price', $variation) != null) {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->where('variation', $variation)->orderBy('id', 'desc')->first();
            return $productPriceUpdate->created_at;
        }
        return null;
    }

    /**
     * Función para obtener el precio recomendado
     * @return void
     */
    public function getRecommendedPrice($variation = null)
    {
        if ($variation != null) {
            return ProductVariationModel::find($variation)->recommended_price;
        } else {
            return $this->recommended_price;
        }
    }

    /**
     * Función para comprobar si tiene un precio customizado
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkCustomPrice()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::getFranchise()->id)->whereNotNull('price')->get();
        if (count($productCustom) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Función para comprobar el tipo de precio custom del precio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function typeCustomPrice()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::getFranchise()->id)->whereNotNull('price');
        if ($productCustom->count() == 0) {
            return 0;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->price_type == 1) {
                return 1;
            } else if ($productCustom->price_type == 2) {
                return 2;
            }
        }
    }

    /**
     * Función para comprobar si el producto esta en promocion
     *    
     */
    public function checkPromotion($productCustom = null)
    {
        /* 27/09/22 Siscu: ja no utilizem customs
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->whereNotNull('promotion');
        else
            $productCustom->where('promotion', '!=', NULL);

        if ($productCustom->count() == 0) {
            return false;
        } else {
            return true;
        }**/
        return $this->promotion;
    }
    /**
     * Función para comprobar si el producto se envia a Canarias
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkCanarias()
    {
        if ($this->shipping_canarias == 1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Comprueba si el producto esta en promociones por defecto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkSuperPromo()
    {
        if ($this->promotion == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprueba que el producto esta en liquidación
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkLiquidation()
    {
        if ($this->liquidation == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprobamos si los productos tiene la oferta 2x1
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkDoubleUnit()
    {
        if ($this->double_unit == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Función para obtener el precio de venta al publico
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPrice($variation = null)
    {
        $price = 0;
        if ($variation != null) {
            $price = ProductVariationModel::find($variation)->price;
        } else {
            if ($this->checkCustomPrice()) {
                $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::getFranchise()->id)->first();
                if ($productCustom->price_type == 1) {
                    $price = $productCustom->price;
                } else {
                    $price = $this->getPublicPriceCost() * (($productCustom->price / 100) + 1);
                }
            } else {
                $price = $this->default_price;
            }
        }
        return $price;
    }

    /**
     * Función para obtener las categorias del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getCategories()
    {
        /**Siscu: 22/7/22 De momento, sólo una categoria  */
        return $this->category;
        //return ProductCategoryModel::where('product', $this->id)->get();
    }

    /**
     * Función para obtener el beneficio de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getProfit($variation = null)
    {
        if ($this->getPrice($variation) != null) {
            return ($this->getPrice($variation) - $this->getPublicPriceCost()) / $this->getPublicPriceCost();
        }
        return 0;
    }

    /**
     * Función para obtener el margen beneficio real del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $front
     * @return void
     */
    public function getProfitMargin($front = false)
    {
        if ($this->getProfit() != null) {
            if ($front == false) {
                return round($this->getProfit() * 100);
            } else {
                if (($this->getProfit() * 100) > $this->getFullPriceMargin()) {
                    return 0;
                } else {
                    return round($this->getProfit() * 100);
                }
            }
        }
        return 0;
    }

    /**
     * Función para obtener el descuento entre el precio de venta y el PVPR
     * @return void
     */
    public function getPublicMarginProfit($variation = null)
    {
        try {
            $publicPrice = $this->getPrice($variation);
            $recommendedPrice = $this->getRecommendedPrice($variation);
            if($recommendedPrice && $recommendedPrice > 0){
                return round((($recommendedPrice - $publicPrice) / $recommendedPrice) * 100);
            }else{
                return 0;
            }
        } catch (\Exception $e) {
            // report($e);
            return 0;
        }
    }

    /**
     * Función para obtener el beneficio entre el precio de coste y el pvpr
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getFullPriceMargin($variation = null)
    {
        if ($this->getRecommendedPrice($variation) != null) {
            $recommendedPrice = $this->getRecommendedPrice($variation);
            $costPrice = $this->getPublicPriceCost(true, $variation);
            return round((($recommendedPrice - $costPrice) / $costPrice) * 100);
        }
        return null;
    }

    /**
     * Función para poner la unidad customizada al producto para la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param array $options
     * @return void
     */
    public function productCustom($options = [])
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id);
        if ($productCustom->count() == 0) {
            $productCustom = new ProductCustomModel();
            $productCustom->product = $this->id;
            $productCustom->franchise = FranchiseModel::getFranchise()->id;
        } else {
            $productCustom = $productCustom->first();
        }
        if ($options['action'] == 'price') {
            if ($options['price'] == null || $options['price_type'] == null) {
                $productCustom->price = null;
                $productCustom->price_type = null;
            } else {
                $publicPrice = $options['price'];
                $costPrice = $this->getPublicPriceCost();
                $margin = round((($publicPrice - $costPrice) / $costPrice) * 100);
                //$discountprice = 
                $provider = $this->getProductProviderData('provider');
                //Megaplus tiene una limitación y no se puede tener el precio custom por debajo del 15% de PVPR
                if ($options['price_type'] == 1){
                    $price = number_format($options['price'], 2, '.', '');
                }
                else{
                    $price = $costPrice * ((number_format($options['price'], 2, '.', '') / 100) + 1);
                }
                $minim_custom_price = $recommendprice - ($recommendprice * 0.15);
                if ($margin < 1 && $options['price_type'] == 1) {
                    return [
                        'status' => false,
                        'message' => 'El precio tiene que tener un beneficio minimo de un 1%',
                        'custom_price' => $this->checkCustomPrice(),
                        'type_custom_price' => $this->typeCustomPrice(),
                        'cost_price' => number_format($this->getPublicPriceCostWithoutIva(), 2, '.', ''),
                        'cost_price_iva' => number_format($this->getPublicPriceCost(), 2, '.', ''),
                        'recommended_price' => number_format($this->getRecommendedPrice(), 2, '.', ''),
                        'price' => number_format($this->getPrice(), 2, '.', ''),
                        'profit_margin' => $this->getProfitMargin(),
                        'full_price_margin' => $this->getFullPriceMargin(),
                    ];
                } else if ($provider == 5 && $minim_custom_price > $price && ($options['price_type'] == 1 || $options['price_type'] == 2)) {
                    return [
                        'status' => false,
                        'message' => 'Condiciones especiales para este proveedor. Descuento máximo sobre PVPR del 15%.',
                        'custom_price' => $this->checkCustomPrice(),
                        'type_custom_price' => $this->typeCustomPrice(),
                        'cost_price' => number_format($this->getPublicPriceCostWithoutIva(), 2, '.', ''),
                        'cost_price_iva' => number_format($this->getPublicPriceCost(), 2, '.', ''),
                        'recommended_price' => number_format($this->getRecommendedPrice(), 2, '.', ''),
                        'price' => number_format($this->getPrice(), 2, '.', ''),
                        'profit_margin' => $this->getProfitMargin(),
                        'full_price_margin' => $this->getFullPriceMargin(),
                    ];
                } else {
                    $productCustom->price = number_format($options['price'], 2, '.', '');
                    $productCustom->price_type = $options['price_type'];
                }
            }
            $productCustom->save();
            return [
                'status' => true,
                'message' => 'Se ha actualizado el precio correctamente',
                'custom_price' => $this->checkCustomPrice(),
                'type_custom_price' => $this->typeCustomPrice(),
                'cost_price' => number_format($this->getPublicPriceCostWithoutIva(), 2, '.', ''),
                'cost_price_iva' => number_format($this->getPublicPriceCost(), 2, '.', ''),
                'recommended_price' => number_format($this->getRecommendedPrice(), 2, '.', ''),
                'price' => number_format($this->getPrice(), 2, '.', ''),
                'profit_margin' => $this->getProfitMargin(),
                'full_price_margin' => $this->getFullPriceMargin(),
            ];
        } else if ($options['action'] == 'promotion') {
            $productCustom->promotion = $options['promotion'];
            $productCustom->save();
            if ($options['promotion'] == 1) {
                return [
                    'status' => true,
                    'message' => 'El producto se ha añadido a promociones correctamente'
                ];
            } else {
                return [
                    'status' => true,
                    'message' => 'El producto se ha quitado de promociones correctamente'
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => 'No se ha enviado una acción valida'
            ];
        }
    }

    /**
     * Función para obtener el nombre del producto segun la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return string
     * @param $productCustom ProductCustomModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getName($productCustom = null)
    {
        /* 27/09/22 Siscu: ja no utilizem customs
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id);

        if ($productCustom->count() == 0) {
            return $this->name;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->name != null) {
                return $productCustom->name;
            } else {
                return $this->name;
            }
        }*/
        return $this->name;
    }

    /**
     * Función para obtener la descripción del producto segun la franquicia
     *    
     */
    public function getDescription($productCustom = null)
    {
        /* 27/09/22 Siscu: ja no utilizem custom
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id);

        if ($productCustom->count() == 0) {
            return $this->description;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->description != null) {
                return $productCustom->description;
            } else {
                return $this->description;
            }
        }*/
        return $this->description;
    }

    /**
     * Método para obtener la descripción corta
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param integer $maxLength
     * @return void
     */
    public function getShortDescription($maxLength = 440)
    {
        $string = strip_tags($this->getDescription());
        $substring = substr($string, 0, $maxLength);
        if (strlen($string) > strlen($substring)) {
            return $substring . '...';
        } else {
            return $substring;
        }
    }

    /**
     * Función para obtener las etiquetas meta personalizadas
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $type
     * @return void
     */
    public function getMetaData($type)
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id);
        if ($productCustom->count() == 0) {
            if ($type == 'meta_title') {
                return $this->getName();
            } else if ($type == 'meta_description') {
                return $this->getShortDescription(250);
            } else if ($type == 'meta_keywords') {
                return null;
            }
        } else {
            $productCustom = $productCustom->first();
            if ($type == 'meta_title') {
                if ($productCustom->meta_title != null) {
                    return $productCustom->meta_title;
                } else {
                    return $this->getName();
                }
            } else if ($type == 'meta_description') {
                if ($productCustom->meta_description != null) {
                    return $productCustom->meta_description;
                } else {
                    return $this->getShortDescription(250);
                }
            } else if ($type == 'meta_keywords') {
                if ($productCustom->meta_keywords != null) {
                    return $productCustom->meta_keywords;
                } else {
                    return null;
                }
            }
        }
    }

    /**
     * Devuelve stock actual, true si no mantenemos stock o false si está agotado.
     * @return boolean
     */
    public function getStock($order = 0, $variation = null)
    {
        if (!$this->unavailable && !$this->discontinued) {
            if ($this->stock_type == 1) {
                $additions = ProductStockModel::where('product_stock.type', '=', 2)->where('product_stock.product', '=', $this->id)->where('product_stock.variation', $variation)->sum('stock');
                $subtractions = ProductStockModel::where('product_stock.type', '=', 1)->where('product_stock.product', '=', $this->id)->where('product_stock.variation', $variation)->sum('stock');
                $stock = $additions - $subtractions;
                if ($stock < 0) $stock = 0;
                return $stock;
            } else if ($this->stock_type == 2) {
                return true;
            } else if ($this->stock_type == 3) {
                if($variation){
                    $stock = ProductVariationModel::find($variation)->stock;
                }else{
                    $stock = $this->stock;
                }
            	$stock = $this->stock;//$this->getProductProvider(false, $variation)->stock;
                return $stock;
                /**Siscu: 22/07/22 reservas canceladas */
                /*
                $stock = $this->getProductProvider(false, $variation)->stock;
                $date = Carbon::now()->subDays(2)->toDateString();
                $reserved = OrderDetailModel::join('orders', 'order_details.order', '=', 'orders.id')
                    ->where('product', $this->id)
                    ->where('variation', $variation)
                    ->where('order', '!=', $order)
                    ->whereDate('orders.created_at', '>=', $date, ' and')
                    ->whereIn('orders.status', [1, 2]);
                $reserved->where(function ($query) use ($date) {
                    $query->where('orders.payment_method', '!=', 6);
                    $query->orWhereNotNull('orders.payment_date');
                    $query->orWhere('orders.status', 2);
                });
                
                return $stock - ($reserved->sum('order_details.units')); */



            } else if ($this->stock_type == 4) {
                return $this->getProductProvider(false, $variation)->stock;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Método para comprobar si se muestran los precios visibles
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return boolean
     */
    public function visiblePrice()
    {
        if ((bool) session('visiblePrice') == 1 || auth()->check()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Método para comporbar si se muetran los descuentos
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return boolean
     */
    public function visibleDiscounts()
    {
        if (!(bool) FranchiseModel::custom('visible_discounts', true) || !$this->getPublicMarginProfit() || $this->getPublicMarginProfit() < 5){
            return false;
        } else {
            return true;
        }
    }

    /**
     * Función para imprimir un banner del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function print()
    {
        //$product = ProductModel::find($this->id);
        $product = $this;
        return view('modules.catalog.product', compact('product'));
    }


    public function hasPhysicalStock(){
        return $this->stock_type == config('settings.stock_types.fisico');
    }

    public function hasDropshippingStock(){
        return $this->stock_type == config('settings.stock_types.dropshipping');
    }

    public function hasLiquidationStock(){
        return $this->stock_type == config('settings.stock_types.liquidacion');
    }

    public function getVariations()
    {        
        return $this->hasMany(ProductVariationModel::class, 'product', 'id');
    }

    public function buildVariations()
    {
        // genera JSON per a Product
        $productVariations = ProductVariationModel::where('product', $this->id)->get();
        $resultArray = array();
        foreach ($productVariations as $productVariations) {
            foreach ($productVariations->variation as $key => $variation) {
                if (!array_key_exists($key, $resultArray)) {
                    $resultArray[$key][0] = $variation;
                } else {
                    if (!in_array($variation, $resultArray[$key])) {
                        $resultArray[$key][] = $variation;
                    }
                }
            }
            // info(print_r($variation, true));
        }

        if(count($resultArray) > 0) {
            $this->variations = json_encode($resultArray);
        } else {
            $this->variations = NULL;
        }        
    }



    /**
     * Actualiza los precios del producto en la tabla de productos (NO ES FA SERVIR)
     *
     * @since 3.0.0
     * @author Eduard <eduardn@devuelving.com>
     * @return void
     */
    public function updateMyShopCostPrice($variation = null)
    {
        if ($this->franchise !== null) {
            // Obtenemos el product provider
            $productProvider = $this->getProductProvider(false, $variation);
            // Obtenemos el proveedor del producto
            $provider = $productProvider->getProvider(false, $variation);
            // Obtenemos el precio de coste y le sumamos el margen de beneficio del proveedor
            $costPrice = $productProvider->cost_price + ($productProvider->cost_price * ($provider->profit_margin / 100));
            if ($variation == null) {
                // Actualitzem els preus de totes les variacions menys les que ja tinguin un preu especific.
                $productVariations = ProductVariationModel::where('product', $this->id)->get();
                foreach ($productVariations as $productVariation) {
                    $productProvider = ProductProviderModel::where('variation', $productVariation->id)->first();
                    if (!$productProvider) {
                        $productVariation->cost_price = $costPrice;
                        $productVariation->save();
                    }
                    /* ProductVariationModel::where('product', $this->id)
                    ->whereNotNull('variation')
                    ->update(['cost_price' => $costPrice, 'price' => $default_price]); */
                }
            }
        }
    }
}
