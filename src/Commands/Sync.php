<?php
namespace Rosshristov\Econt\Commands;

use App;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Rosshristov\Econt\Econt;
use Rosshristov\Econt\Models\Neighbourhood;
use Rosshristov\Econt\Models\Office;
use Rosshristov\Econt\Models\Region;
use Rosshristov\Econt\Models\Settlement;
use Rosshristov\Econt\Models\Street;
use Rosshristov\Econt\Models\Zone;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     * Default import only BG stuff
     * To import all, use "all" argument
     *
     * @var string
     */
    protected $signature = 'econt:sync {import=bg}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes the database with the Econt\'s one through their API. Caution: This is slow operation with heavy load.';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function handle()
    {
        $time = time();
        $bgCities = [];
        DB::connection('mysql')->disableQueryLog();

        $this->comment(PHP_EOL . 'Starting...');

        $this->comment(PHP_EOL . 'Importing zones and settlements... Please wait.');

        Zone::truncate();
        Settlement::truncate();

        foreach (App::make(Econt::class)->zones() as $zone) {
            if ($this->argument('import') === 'bg' && $zone['national'] != 1) {
                continue;
            }

            (new Zone)->import($zone);

            $zone_id = Arr::has($zone, 'id') ? Arr::get($zone, 'id') : 0;

            foreach (App::make(Econt::class)->settlements($zone_id) as $settlement) {
                if (!is_array($settlement)) {
                    continue;
                }

                if ($settlement['id_country'] == 1033) {
                    $bgCities[] = $settlement['id'];
                }

                (new Settlement)->import($settlement);
            }
        }

        $this->comment(PHP_EOL . 'Zones and settlements imported successfully.');

        $this->comment(PHP_EOL . 'Importing regions... Please wait.');

        Region::truncate();

        foreach (App::make(Econt::class)->regions() as $region) {
            if (($this->argument('import') === 'bg') && !in_array(($region['id_city'] ?? null), $bgCities)) {
                continue;
            }

            (new Region)->import($region);
        }

        $this->comment(PHP_EOL . 'Regions imported successfully.' . PHP_EOL);

        $this->comment(PHP_EOL . 'Importing neighbourhoods... Please wait.');

        Neighbourhood::truncate();

        foreach (App::make(Econt::class)->neighbourhoods() as $region) {

            if (($this->argument('import') === 'bg') && !in_array(($region['id_city'] ?? null), $bgCities)) {
                continue;
            }

            (new Neighbourhood)->import($region);
        }

        $this->comment(PHP_EOL . 'Neighbourhoods imported successfully.' . PHP_EOL);

        $this->comment(PHP_EOL . 'Importing streets... Please wait.');

        Street::truncate();

        foreach (App::make(Econt::class)->streets() as $region) {

            if (($this->argument('import') === 'bg') && !in_array(($region['id_city'] ?? null), $bgCities)) {
                continue;
            }

            (new Street)->import($region);
        }

        $this->comment(PHP_EOL . 'Streets imported successfully.' . PHP_EOL);

        $this->comment(PHP_EOL . 'Importing offices... Please wait.');

        Office::truncate();

        foreach (App::make(Econt::class)->offices() as $region) {

            if (($this->argument('import') === 'bg') && !in_array(($region['id_city'] ?? null), $bgCities)) {
                continue;
            }

            (new Office)->import($region);
        }

        $this->comment(PHP_EOL . 'Offices imported successfully.' . PHP_EOL);

        $this->comment(PHP_EOL . sprintf('Finished in %f minutes.', (time() - $time) / 60));
    }

}
