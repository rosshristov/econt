<?php
namespace Rosshristov\Econt\Http\Controllers;

use App;
use Input;

use App\Http\Controllers\Controller;
use Rosshristov\Econt\Models\Office;
use Rosshristov\Econt\Helpers\Locale;
use Rosshristov\Econt\Models\Settlement;

class OfficeController extends Controller
{

    const MIN_AUTOCOMPLETE_LENGTH = 3;

    public function index()
    {
        return Office::orderBy(Locale::name())->get();
    }

    public function show($id)
    {
        return Office::with('Settlement')->findOrNew($id);
    }

    public function dropdown()
    {
        return Office::orderBy(Locale::name())->pluck('name', 'id');
    }

    public function autocomplete()
    {
        $settlement = (int)$this->request->get('settlement');
        $name = htmlentities($this->request->get('query'), ENT_QUOTES, 'UTF-8', false);

        if (0 >= $settlement) {
            return ['results' => [], 'more' => false];
        }

        $result = Office::where('city_id', $settlement)
            ->whereNested(function ($query) use ($name) {
                $query->where('name', 'LIKE', "%$name%")->orWhere('name_en', 'LIKE', "%$name%");
            })
            ->get(['id', 'bg' === App::getLocale() ? 'name' : 'name_en AS name']);

        return [
            'results' => $result,
            'more' => false,
        ];
    }

    public function autocompleteBySettlementName()
    {
        $settlement = htmlentities($this->request->get('settlement'), ENT_QUOTES, 'UTF-8', false);
        $name = htmlentities($this->request->get('query'), ENT_QUOTES, 'UTF-8', false);

        $settlement = Settlement::where('name', 'LIKE', "$settlement%")->orWhere('name_en', 'LIKE', "$settlement%")->first();

        if (!$settlement) {
            return ['results' => [], 'more' => false];
        }

        $result = Office::where('city_id', $settlement->id)
            ->whereNested(function ($query) use ($name) {
                $query->where('name', 'LIKE', "%$name%")->orWhere('name_en', 'LIKE', "%$name%");
            })
            ->get(['id', 'bg' === App::getLocale() ? 'name' : 'name_en AS name']);

        return [
            'results' => $result,
            'more' => false,
        ];
    }

}
