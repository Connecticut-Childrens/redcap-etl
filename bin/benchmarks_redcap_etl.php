<?php

#-------------------------------------------------------------
# This is the batch script for REDCap-ETL: Extracting data
# from REDCap, transforming REDCap Records into Tables/Rows,
# and loading the data into a data store.
# This is the script that would typically be used for cron
# jobs to run ETL automatically at scheduled times.
#
# Options:
#    -c <configuration-file>
#
#-------------------------------------------------------------

require(__DIR__.'/../dependencies/autoload.php');

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Version;
use IU\REDCapETL\Logger;

/**
 * @BeforeMethods({"init"})
 * @AfterMethods({"destroy"})
 * @OutputTimeUnit("seconds", precision=3)
 */
class benchmarks_redcap_etl
{
    private $app;
    private $logger;

    public function init() {
        $this->app = basename(__FILE__, '.php');
        $this->logger = new Logger($this->app);

        $this->logger->setPrintInfo(false);
    }

    public function destroy() {
        unset($this->app);
        unset($this->logger);
    }

    /**
     * @return Generator
     */
    public function batchSizes() {
        yield ['batchSize' => '100'];
        yield ['batchSize' => '500'];
        yield ['batchSize' => '1000'];
        yield ['batchSize' => '1500'];
        yield ['batchSize' => '2000'];
        yield ['batchSize' => '5000'];
    }

    /**
     * @return Generator
     */
    public function configFiles() {
        yield ['configFile' => 'metricsClassicNonRepeating.ini'];
        yield ['configFile' => 'metricsClassicRepeating.ini'];
        yield ['configFile' => 'metricsRepeatingEvents.ini'];
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     * @ParamProviders({"batchSizes", "configFiles"})
     * @Sleep(10000000)
     */
    public function bench_main($params) {
        $configFile = 'config/'. $params['configFile'];

        try {
            $redCapEtl = new RedCapEtl($this->logger, $configFile);
            $redCapEtl->getConfiguration()->setBatchSize($params['batchSize']);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            $this->logger->logException($exception);
            $this->logger->logError('Processing failed.');
        }
    }
}
