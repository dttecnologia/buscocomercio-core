<?php

namespace  Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandModel extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brand';

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
        'slug', 'name', 'image', 'franchise', 'meta_title', 'meta_description', 'meta_keywords'
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
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name'],
                'unique' => true,
                'onUpdate' => true,
                'maxLength' => 191,
                'includeTrashed' => true
            ]
        ];
    }

    public function scopeWithUniqueSlugConstraints(Builder $query, Model $model, $attribute, $config, $slug) {
        return $query->where('franchise', $model->franchise);
    }
    
    /**
     * Función para imprimir un banner de la marca
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function print()
    {
        return view('modules.catalog.brand');
    }
}
