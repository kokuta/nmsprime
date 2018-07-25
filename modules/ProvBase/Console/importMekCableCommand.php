<?php

namespace Modules\provbase\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Log;

use Modules\ProvBase\Entities\Contract;
use Modules\ProvBase\Entities\Modem;
use Modules\ProvBase\Entities\Qos;
use Modules\ProvBase\Entities\Configfile;

use Modules\BillingBase\Entities\Item;
use Modules\BillingBase\Entities\Product;
use Modules\BillingBase\Entities\SepaMandate;

class MethodAlreadyDoneExeption extends \Exception
{
}

class importMekCableCommand extends Command
{
	/**
	 * The console command name.
	 */
	protected $name = 'nms:importMekCable';

	/**
	 * The console command description.
	 */
	protected $description = 'Imports data from MEK-cable {mode}';

	/**
	 * The signature (defining the optional argument)
	 */
	protected $signature = 'nms:importMekCable
                            {mode : the mode to run in – atm there is only “really” accepted :-)}';

    /**
     * the storage subdir we expect input data in
     */
    protected $storage_path = 'storage/app/tmp/mek-cable-import-data';
    protected $storage_path_processed = 'storage/app/tmp/mek-cable-import-data/processed';

    /**
     * The models to be processed.
     */
    protected $data_to_be_processed = [
        'qos',
        /* 'contract', */
    ];


	/**
	 * Execute the console command.
	 *
     * @author Patrick Reichel
	 * @return null
	 */
    public function fire() {

        if ($this->argument('mode') != 'really') {
            echo "\nERROR: mode has to be set to really";
            echo "\nMake sure that km3 import has been run before calling this again!";
            echo "\n\n";
            exit(1);
        }

        foreach ($this->data_to_be_processed as $data) {
            $this->_process($data);
        }
    }


    /**
     * Reads a CSV file and returns its contents as array.
     *
     * @author Patrick Reichel
     *
     * @param $csv_file file to read
     * @return array
     */
    public function read_csv_file($csv_file) {

        if (!file_exists($this->storage_path."/".$csv_file) && file_exists($this->storage_path."/processed/".$csv_file)) {
            throw new MethodAlreadyDoneExeption;
        }

        try {
            $fh = fopen($this->storage_path."/".$csv_file, 'r');
            $header = fgetcsv($fh);
            $csv = [];
            while(($row = fgetcsv($fh)) !== FALSE) {
                $csv[] = array_combine($header, $row);
            }
        } catch (\Exception $ex) {
            $this->error("ERROR reading CSV file $csv_file: ".$ex->getMessage());
            echo "\n";
            echo "Exiting…";
            exit(1);
        }
        finally {
            fclose($fh);
        }

        return $csv;

    }


    /**
     * Updates/creates contracts depending on existance of legacy contract numbers.
     *
     * @author Patrick Reichel
     */
    protected function _process($d) {

        try {
            $csv = $this->read_csv_file("$d.csv");
        } catch (MethodAlreadyDoneExeption $ex) {
            $this->info("Skipping $d – seems that it already has been processed");
            return True;
        }

        $method = "_process_$d";
        if ($this->$method() === True) {
            $from = $this->storage_path."/$d.csv";
            $to = $this->storage_path_processed."/$d.csv";
            if (!file_exists($this->storage_path_processed)) {
                mkdir($this->storage_path_processed);
            }
            rename($from, $to);
        }

    }

    /**
     * Updates/creates contracts depending on existance of legacy contract numbers.
     *
     * @author Patrick Reichel
     */
    protected function _process_qos() {
        return true;
    }

}
