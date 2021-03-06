<?php
namespace Rosshristov\Econt\Http\Controllers;

use App;
use Lang;
use Input;

use App\Http\Controllers\Controller;
use Rosshristov\Econt\Models\Settlement;

class SettlementController extends Controller
{

    const MIN_AUTOCOMPLETE_LENGTH = 3;

    public function index()
    {
        return Settlement::with('country')->orderBy('name')->get();
    }

    public function autocomplete()
    {
        $name = htmlentities($this->request->get('query'), ENT_QUOTES, 'UTF-8', false);

        if (self::MIN_AUTOCOMPLETE_LENGTH > mb_strlen($name)) {
            return ['results' => [], 'more' => false];
        }

        $settlements = Settlement::where('name', 'LIKE', "%$name%")->orWhere('name_en', 'LIKE', "%$name%")->get([
            'id',
            'type',
            'name',
            'name_en',
            'post_code',
        ]);
        $result = [];

        foreach ($settlements as $settlement) {
            $entry = [ 'id' => $settlement->id, 'name' => $settlement->formatted ];
            $entry['ref'] = $settlement->reference;
            $entry['post_code'] = $settlement->post_code;

            $result[] = (object) $entry;
        }

        return [
            'results' => $result,
            'more' => false,
        ];
    }

}
