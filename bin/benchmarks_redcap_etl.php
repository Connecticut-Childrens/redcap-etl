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

class RedcapEtlBench
{
    /**
     * @Revs(1)
     * @Iterations(1)
     */
    public function benchMetrics_classicNonRepeating()
    {
        $app = basename(__FILE__, '.php');
        $logger = new Logger($app);

        $logger->setPrintInfo(false);

        $configFile = 'config/classic.ini';

        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            $logger->logException($exception);
            $logger->logError('Processing failed.');
        }
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     */
    public function benchMetrics_classicRepeating()
    {
        $app = basename(__FILE__, '.php');
        $logger = new Logger($app);

        $logger->setPrintInfo(false);

        $configFile = 'config/classic.ini';

        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            $logger->logException($exception);
            $logger->logError('Processing failed.');
        }
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     */
    public function benchMetrics_repeatingEvents()
    {
        $app = basename(__FILE__, '.php');
        $logger = new Logger($app);

        $logger->setPrintInfo(false);

        $configFile = 'config/classic.ini';

        try {
            $redCapEtl = new RedCapEtl($logger, $configFile);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            $logger->logException($exception);
            $logger->logError('Processing failed.');
        }
    }
}
