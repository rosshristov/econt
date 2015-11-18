<?php
namespace Rolice\Econt\Http\Controllers;

use App\Http\Controllers\Controller;
use Rolice\Econt\Models\Office;
use Rolice\Econt\Helpers\Locale;

class OfficeController extends Controller
{
    public function index()
    {
        return Office::orderBy(Locale::name())->get();
    }

    public function dropdown()
    {
        return Office::orderBy(Locale::name())->lists('name', 'id');
    }

    public function autocomplete()
    {
        $lang = Input::get('lang');
        $name = htmlentities(Input::get('query'), ENT_QUOTES, 'UTF-8', false);

        if(self::MIN_AUTOCOMPLETE_LENGTH > mb_strlen($name)) {
            return [ 'results' => [], 'more' => false ];
        }

        $result = Office::where('name', 'LIKE', "%$name%")->orWhere('name_en', 'LIKE', "%$name%")->get(['id', 'bg' === $lang ? 'name' : 'name_en AS name']);

        return [
            'results' => $result,
            'more' => false,
        ];
    }
}