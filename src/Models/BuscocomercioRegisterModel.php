<?php

namespace  Buscocomercio\Core;

use Buscocomercio\Core\FranchiseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuscocomercioRegisterModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'buscocomercio_register';

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
        'franchise', 'agent', 'payment_type', 'payment_id', 'amount', 'discount_id', 'email', 'token', 'contact_data', 'billing_data', 'shop_data', 'domain_data', 'notes', 'check_data', 'check_domain', 'check_payment', 'check_franchise', 
        'redsys_merchant_identifier', 'redsys_expiry_date', 'payment_amount', 'pending_payments', 'next_payment', 'options'
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
     * Función para obtener los datos de un cliente
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param string $data
     * @return void
     */
    public function getData($data)
    {
        return $this->$data;
    }

    /**
     * Método para obtener los datos de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function franchise()
    {
        return FranchiseModel::find($this->franchise);
    }
}
