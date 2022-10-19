<?php

namespace  Buscocomercio\Core;

use Illuminate\Database\Eloquent\Model;

class AdminModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin';

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
        'name', 'email', 'password', 'role', 'supervisor', 'headquarter', 'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at',
    ];
    
    /**
     * 
     *
     * @since
     * @author
     * @return Array
     */
    public function children()
    {
        return $this->hasMany(AdminModel::class, 'headquarter', 'id')->with(['children']);
    }
    /**
     * 
     *
     * @since
     * @author
     * @return Array
     */
    public function allChildren()
    {
        return $this->children()->with(['allChildren']);
    }
}
