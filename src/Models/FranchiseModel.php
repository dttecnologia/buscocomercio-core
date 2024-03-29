<?php

namespace  Buscocomercio\Core;

use Carbon\Carbon;
use Buscocomercio\Core\CustomerModel;
use Illuminate\Database\Eloquent\Model;
use Buscocomercio\Core\CallAppointmentModel;
use Buscocomercio\Core\FranchiseServicesModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Buscocomercio\Core\FranchiseModel;

class FranchiseModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise';

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
        'code', 'status', 'agent', 'name', 'domain', 'domain_status', 'domain_provider', 'company_type', 'start', 'irpf', 'bank_account', 'options', 'type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];
    /**
     * Settings FranchiseCustomModel == All data where franchiseId
     *
     * @var array
     */
    static protected $settings;
    
    /**
     * Services FranchiseServicesModel == All data where franchiseId
     *
     * @var array
     */
    static protected $services;
    /**
     * Método para obtener el logo de la franquicia
     *
     * @return void
     */
    public static function getLogo($redirect = true)
    {
        try {
            $franchise = new FranchiseModel();
            $franchiseLogo = $franchise->getCustom('logo');
            if ($franchiseLogo != null) {
                // return config('app.cdn.url') . $franchise->getCustom('logo');
                // return '/cdn/' . $franchise->getCustom('logo');
                if ($redirect){
                return route('index') . '/cdn/' . $franchiseLogo;
                }
                else
                {
                return config('app.cdn.url') . $franchiseLogo;
                }
            } 
            return asset('assets/images/brand/logo.png');
        } catch (\Exception $e) {
            return asset('assets/images/brand/logo.png');
        }
    }

    /**
     * Método para obtener el icono de la franquicia
     *
     * @return void
     */
    public static function getIcon()
    {
        try {
            $franchise = new FranchiseModel();
            if ($franchise->getCustom('icon') != null) {
                // return config('app.cdn.url') . $franchise->getCustom('icon');
                // return '/cdn/' . $franchise->getCustom('icon');
                return route('index') . '/cdn/' . $franchise->getCustom('icon');
            }
            return asset('assets/images/brand/icon.png');
        } catch (\Exception $e) {
            return asset('assets/images/brand/icon.png');
        }
    }

    /**
     * Método para obtener el dominio de la franquicia actual
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public static function getDomain()
    {
        $domain = $_SERVER['HTTP_HOST'];
        $domain = str_replace("www.", "", $domain);
        return $domain;
    }

    /**
     * Método para obtener la franquicia por el dominio o usuario
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public static function getFranchise()
    {
        if (session()->exists('franchise')){
            //info('FranchiseModel:: Tengo franquicia en sesión->'.session('franchise'));
            return session('franchise');
        }else if (!empty(auth()->user()->franchise)) {
           //info('FranchiseModel:: No hay franquicia en sesión. La busco y la guardo');
           $franchise = FranchiseModel::find(auth()->user()->franchise);
           session()->put('franchise', $franchise);
           //info('FranchiseModel:: Ahora sí, la franquicia en sesión->'.session('franchise'));
           return $franchise;
        } else if ($franchise = FranchiseModel::where('domain', FranchiseModel::getDomain())->first()) {
            session()->put('franchise', $franchise);            
            //info('FranchiseModel:: entro por dominio sin sesión la busco y la guardo->'.session('franchise'));
            return $franchise;
        } else {
            $franchise = FranchiseModel::where('id', str_replace('.buscocomercio.com.es', '', FranchiseModel::getDomain()))->first();
            session()->put('franchise', $franchise);
            info('FranchiseModel:: entro por código sin sesión la busco y la guardo->'.session('franchise'));            
            return $franchise;
        }
        
        /******* cambio 8/2/21 */
        /*if (!empty(auth()->user()->franchise)) {
            return FranchiseModel::find(auth()->user()->franchise);
        } else if (session()->exists('franchise')){
            return session('franchise');
        } else if ($franchise = FranchiseModel::where('domain', FranchiseModel::getDomain())->first()) {
            session()->put('franchise', $franchise);
            return $franchise;
        } else {
            return FranchiseModel::where('code', str_replace('.tutienda.com.es', '', FranchiseModel::getDomain()))->first();
        }*/
    }


    /**
     * Metodo para obtener al franquiciado del dominio
     *
     * @since 3.0.0
     * @author Aaron <soporte@buscocomercio.com>
     * @return void
     */
    public static function getFranchiseContactData($franchise = NULL) 
    {
        if(!$franchise) {
            $franchise = FranchiseModel::getFranchise()->id;
        }
        return FranchiseContactDataModel::where('franchise', $franchise)->first();
    }

    /**
     * Función para obtener datos de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public static function get($data = null)
    {
        $id = FranchiseModel::getFranchise()->id;
        if ($data) {
            try {
                $franchise = FranchiseModel::find($id);
                return $franchise->$data;
            } catch (\Exception $e) {
                // report($e);
                return null;
            }
        }
        return $id;
    }

    /**
     * Función para obtener la lista de clientes de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public function countClients()
    {
        $clients = CustomerModel::where('franchise', $this->id)->get();
        return count($clients) - 1;
    }

    /**
     * Función para obtener las variables perosnalizadas de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public static function getCustom($key = null)
    {      
        
        try {
            
            if (empty(self::$settings)) {
                self::$settings = FranchiseCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->get()->pluck("value", "var");                
            }

            if($key !== null && isset(self::$settings[ $key ])) {
                return self::$settings[ $key ];
            }

            return null;

        } catch (\Exception $e) {
            // report($e);
            return null;
        }

    }
    
    /**
     * Método para obtener variables customizadas de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param string $var
     * @param string $default
     * @return string
     */
    public static function custom($var, $default = null)
    {        
        try {
            $custom = FranchiseModel::getCustom($var);
            return ($custom != null) ? $custom : $default;
        } catch (\Exception $e) {
            return $default;
        }      
    }
    /**
     * Función para obtener las servicios perosnalizadas de la franquicia
     *
     * @since 3.0.0
     * @author 
     * @return void
     */
    public static function getServices($data = null)
    {
        if (empty(self::$services)) {
            self::$services = FranchiseServicesModel::where('franchise', FranchiseModel::getFranchise()->id)->get()->pluck("value", "service");
        }
        if($data !== null && isset(self::$services[ $data ])) {
            return self::$services[ $data ];
        }
        return null;
    }
    /**
     * Método para obtener servicios de la franquicia
     *
     * @since 3.0.0
     * @author 
     * @param string $var
     * @param string $default
     * @return void
     */
    public static function services($var, $default = null)
    {
        try {
            $service = FranchiseModel::getServices($var);
            return ($service != null) ? $service : $default;
        } catch (\Exception $e) {
            return $default;
        }      
    }
    /**
     * Función para obtener las citas telefonicas de la franquicia
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @param string $type
     * @param date $date
     * @param string $date
     * @return void
     */
    public function getBooking($type = null, $date = null, $format = null)
    {
        $callAppointment = CallAppointmentModel::where('franchise', $this->id);
        if ($type != null) {
            $callAppointment->where('type', $type);
        }
        if ($date != null) {
            $callAppointment->where('date', $date);
        }
        if ($callAppointment->count() == 0) {
            return 'Sin Cita';
        }
        $callAppointments = $callAppointment->get();
        if ($format == 'text') {
            $return = '';
            foreach ($callAppointments as $callAppointment) {
                $return = 'Fecha: ' . Carbon::createFromFormat('Y-m-d', $callAppointment->date)->format('d-m-Y') . '<br>Hora: ' . substr($callAppointment->time, 0, -3) . '<br>';
            }
            return $return;
        }
        return $callAppointments;
    }
}
