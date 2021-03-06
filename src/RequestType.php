<?php
namespace Rosshristov\Econt;

use Rosshristov\Econt\Exceptions\EcontException;

/**
 * Class RequestType
 * Class with constants, providing valid, predefined Econt request types.
 * @package Rosshristov\Econt
 * @version 1.0
 * @access public
 */
class RequestType
{
    const NONE = null;
    const ZONES = 'cities_zones';
    const REGIONS = 'cities_regions';
    const STREETS = 'cities_streets';
    const SHIPMENTS = 'shipments';
    const SHIPPING = 'shipping';
    const CITIES = 'cities';
    const CANCELLATION = 'cancel_shipments';
    const NEIGHBOURHOODS = 'cities_quarters';
    const OFFICES = 'offices';
    const PROFILE = 'profile';
    const COMPANY = 'access_clients';
    const DELIVERY = 'delivery_days';
}
