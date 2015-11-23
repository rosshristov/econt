<?php
namespace Rolice\Econt;

use App;

use rest\server\user\Load;
use Rolice\Econt\Components\Loading;
use Rolice\Econt\Components\Sender;
use Rolice\Econt\Components\Receiver;
use Rolice\Econt\Components\Shipment;
use Rolice\Econt\Components\Payment;
use Rolice\Econt\Components\Services;

/**
 * Class Waybill
 * Interface exported by this package to allow creating/issuing new Econt waybills for printing.
 * @package Rolice\Econt
 * @version 0.1
 * @access public
 */
class Waybill
{
    protected static function _call(Loading $loading, $calc)
    {
        $data = [
            'system' => [
                'validate' => 0,
                'response_type' => 'XML',
                'only_calculate' => (int)!!$calc,
            ],
            'loadings' => [
                $loading,
            ],
        ];

        $econt = App::make('Econt');
        $waybill = $econt->request(RequestType::SHIPPING, $data, Endpoint::parcel());

        return $waybill;
    }

    public static function issue(Loading $loading)
    {
        return self::_call($loading, false);
    }

    public static function calculate(Loading $loading)
    {
        return self::_call($loading, true);
    }
}