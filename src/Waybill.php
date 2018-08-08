<?php
namespace Rosshristov\Econt;

use App;

use rest\server\user\Load;
use Rosshristov\Econt\Components\Loading;
use Rosshristov\Econt\Components\Sender;
use Rosshristov\Econt\Components\Receiver;
use Rosshristov\Econt\Components\Shipment;
use Rosshristov\Econt\Components\Payment;
use Rosshristov\Econt\Components\Services;
use Rosshristov\Econt\Exceptions\EcontException;

/**
 * Class Waybill
 * Interface exported by this package to allow creating/issuing new Econt waybills for printing.
 * @package Rosshristov\Econt
 * @version 1.0
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

        $econt = App::make(Econt::class);
        $waybill = $econt->request(RequestType::SHIPPING, $data, Endpoint::parcel());

        if(!isset($waybill['result']) || !isset($waybill['result']['e'])) {
            throw new EcontException('Invalid response from Econt parcel service.');
        }

        if(isset($waybill['result']['e']['error']) && $waybill['result']['e']['error']) {
            throw new EcontException(strip_tags(preg_replace('#<br[\s\t\r\n]*/?>#', "\n", $waybill['result']['e']['error'])));
        }

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
