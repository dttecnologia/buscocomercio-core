<?php

namespace  Buscocomercio\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryModel extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category';

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
        'slug', 'name', 'description', 'description_large', 'parent', 'has_products', 'franchise', 'meta_title', 'meta_description', 'meta_keywords', 'img_big', 'img_small'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
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

    public function scopeWithUniqueSlugConstraints(Builder $query, Model $model, $attribute, $config, $slug) 
    {
        return $query->where('franchise', $model->franchise);
    }

    /**
     * Función para obtener un listado con las categorías
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param int $parent
     * @return void
     */
    public function arrayCategories($parent = 0)
    {
        $return = [];
        $categories = CategoryModel::whereNull('franchise')->where('parent', $parent)->get();
        foreach ($categories as $category) {
            $child_categories = [];
            if ($category->has_products == 0) {
                $child_categories = $category->arrayCategories($category->id);
            }
            $return[] = [
                'id' => $category->id,
                'name' => $category->name,
                'parent' => $category->parent,
                'has_products' => $category->has_products,
                'image' => config('app.cdn.url') . $category->getImage(),
                'edit' => route('category.edit', $category->id),
                'child_categories' => $child_categories
            ];
        }
        return $return;
    }

    /**
     * Función para obtener un listado con las categorías
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param int $parent
     * @return void
     */
    public function arrayCategoriesOwnShop($parent = 0)
    {
        $return = [];
        $categories = CategoryModel::where('franchise', \Auth::User()->franchise)->where('parent', $parent)->get();
        foreach ($categories as $category) {
            $child_categories = [];
            if ($category->has_products == 0) {
                $child_categories = $category->arrayCategoriesOwnShop($category->id);
            }
            $return[] = [
                'id' => $category->id,
                'name' => $category->name,
                'parent' => $category->parent,
                'has_products' => $category->has_products,
                'image' => config('app.cdn.url') . $category->getImage(),
                'edit' => route('own-shop.category.edit', $category->id),
                'child_categories' => $child_categories
            ];
        }
        return $return;
    }

    /**
     * Método para devolver la imagen de la categoria
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function getImage()
    {
        $categoryImage = DB::table('category_image')->where('category', $this->id)->whereNull('franchise');
        if ($categoryImage->count() > 0) {
            foreach ($categoryImage->get() as $category) {
                return $category->image;
            }
        } else {
            return 'default.png';
        }
    }

    /**
     * Método para devolver la categoria padre
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function getParent()
    {
        return CategoryModel::find($this->parent);
    }

    /**
     * Función para obtener el nombre de la categoria con todas las padres
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param int $parent
     * @param string $icon
     * @return void
     */
    public function listCategoriesName($parent, $icon = "<i class='fas fa-angle-double-right'></i>")
    {
        if ($parent != 0) {
            $category = CategoryModel::find($parent);
            return $category->listCategoriesName($category->parent, '>') . ' ' . $icon . ' ' . $category->name;
        }
    }

    /**
     * Función para imprimir un banner de la categoria
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function print()
    {
        $category = CategoryModel::find($this->id);
        return view('modules.catalog.category', compact('category'));
    }

    /**
     * 
     *
     * @since
     * @author
     * @return Array 
     */
    public function childs() 
    {
        return $this->hasMany(CategoryModel::class, 'parent', 'id')->with('childs.custom')->orderBy('category.name');
    }

    /**
     * 
     *
     * @since
     * @author
     * @return CategoryCustomModel
     */
    function custom() {
        return $this->hasOne(CategoryCustomModel::class, 'category')->where('franchise', FranchiseModel::getFranchise()->id);
    }

    /**
     * 
     *
     * @since
     * @author
     * @return Array
     */
    public function children()
    {
        return $this->hasMany(CategoryModel::class, 'parent', 'id')->with(['children:id,parent,slug,name,description,meta_title,meta_keywords', "custom", "image", "customImage"])->orderBy('category.name');
    }

    /**
     * 
     *
     * @since
     * @author
     * @return Array
     */
    public function childrenCustom()
    {
        return $this->hasMany(\App\Category::class, 'parent', 'id')->with('childrenCustom.custom')
            ->selectRaw("category.*, IF(category_custom.name IS NULL, category.name, category_custom.name) AS custom_name")
            ->leftJoin("category_custom", function($q) {
                $q->on('category_custom.category', '=', 'category.id');
                $q->where('category_custom.franchise', '=', FranchiseModel::getFranchise()->id);
            })
            ->orderBy('custom_name');
    }
}
