<?php
namespace Rosshristov\Econt\Http\Controllers;

use Input;

use App\Http\Controllers\Controller;
use Rosshristov\Econt\Components\Loading;
use Rosshristov\Econt\Components\Receiver;
use Rosshristov\Econt\Components\Sender;
use Rosshristov\Econt\Econt;
use Rosshristov\Econt\Models\Neighbourhood;
use Rosshristov\Econt\Models\Office;
use Rosshristov\Econt\Models\Settlement;
use Rosshristov\Econt\Models\Street;
use Rosshristov\Econt\Models\Zone;
use Rosshristov\Econt\Waybill;

class EcontController extends Controller
{
    public function zones()
    {
        return Zone::orderBy('name')->get();
    }

    public function neighbourhoods()
    {
        return Neighbourhood::orderBy('name')->get();
    }

    public function profile()
    {
        $username = $this->request->get('username');
        $password = $this->request->get('password');

        Econt::setCredentials($username, $password);
        return Econt::profile();
    }

    public function company()
    {
        $username = $this->request->get('username');
        $password = $this->request->get('password');

        Econt::setCredentials($username, $password);
        return Econt::company();
    }


}
