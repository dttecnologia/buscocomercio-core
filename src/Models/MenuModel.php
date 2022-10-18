<?php

namespace  Buscocomercio\Core;

class MenuModel
{
    /**
     * Devuelve la estructura base del menú
     *
     * @since 3.0.0
     * @author Soporte <soporte@buscocomercio.com>
     * @return void
     */
    public static function getDefaultMenu()
    {
        /*return  [
            ['type' => 0, 'text' => 'Inicio'],
            ['type' => 2, 'text' => 'Hogar', 'category' => 1],
            ['type' => 1, 'text' => 'Perfumes y Cosmética', 'category' => 2],
            ['type' => 1, 'text' => 'Cuidado e Higiene', 'category' => 3],
            ['type' => 1, 'text' => 'Parafarmacia', 'category' => 4],
            ['type' => 1, 'text' => 'Automóvil', 'category' => 5],
            ['type' => 1, 'text' => 'Nutrición Sport', 'category' => 6],
            ['type' => 2, 'text' => 'Dietética Natural', 'category' => 7],
            ['type' => 1, 'text' => 'Tecnología', 'category' => 8],
            ['type' => 1, 'text' => 'Más Categorías', 'category' => 2367]
        ];*/
        return  [
            ['type' => 0, 'text' => 'Inicio'],
            ['type' => 3, 'text' => 'Contacto', 'link' => '/contact']
        ];
    }
    /**
     * Devuelve la estructura base del menú secundario
     *
     * @return void
     */
    public static function getDefaultMenuFooter()
    {
        /*
        return  [ 
            ['type' => 0, 'text' => 'Inicio'],
            ['type' => 5, 'text' => 'Menú Principal']
        ];*/ 
        return [
            ["type"=>4,"text"=>"MI CUENTA","elements"=>[
                ["text"=>"Inicio","category"=>null,"link"=>"/","newPage"=>false],
                ["text"=>"Contacta","category"=>null,"link"=>"/contact","newPage"=>false],
                ["text"=>"Mi Cuenta","category"=>null,"link"=>"/auth/login","newPage"=>false]
            ]
            ],

            ["type"=>4,"text"=>"ENLACES PRINCIPALES","elements"=>[
                ["text"=>"Términos y condiciones","category"=>null,"link"=>"/page/terms-and-conditions","newPage"=>false],
                ["text"=>"Política de privacidad y cookies","category"=>null,"link"=>"/page/privacity-policy-cookies","newPage"=>false]
            ]
            ]
            ];      
   }
}
