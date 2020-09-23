<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Test of the REDCap-ETL script on the dynamic-rules project. ???As a result, line
 * coverage statistics will not be collected for these tests, and a corresponsing test
 * that runs using methods calls was created to get line coverage.
 */
class DynamicRulesTest extends TestCase
{
    const CONFIG_FILE = __DIR__.'/../config/dynamic-rules.ini';
    const OUTPUT_DIR   = __DIR__.'/../output/';     # directory with ETL results
    const TEST_DATA_DIR   = __DIR__.'/../data/';    # directory with test data comparison files

    private static $config;
    private static $csvDir;
    private static $logger;

    public static function setUpBeforeClass()
    {
        if (file_exists(self::CONFIG_FILE)) {
            self::$logger = new Logger('dynamic_rules_test');
            self::$config = new Configuration(self::$logger, self::CONFIG_FILE);
            self::$csvDir = str_ireplace(
                'CSV:',
                '',
                self::$config->getDbConnection()
            );
        }
    }

    public function setUp()
    {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->markTestSkipped("Required configuration not set for this test.");

        } 
    }

    public static function deleteOldResultsFiles()
    {
        $file = self::$csvDir . "/registration.csv";
        if (file_exists($file)) {
            unlink($file);
        }

        $file = self::$csvDir . "/registration_label_view.csv";
        if (file_exists($file)) {
            unlink($file);
        }

        $file = self::$csvDir . "/weight.csv";
        if (file_exists($file)) {
            unlink($file);
        }

        $file = self::$csvDir . "/emergency.csv";
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testAutoGenAllFalse()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form
        $expectedFile = self::TEST_DATA_DIR . 'dynamic_rules_registration.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest registration form check'
        );

        # Check registration label view
        $expectedFile = self::TEST_DATA_DIR
            . 'dynamic_rules_registration_label_view.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'registration_label_view.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest registration form label view check'
        );

        # Check weight form
        $expectedFile = self::TEST_DATA_DIR . 'dynamic_rules_weight.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'weight.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest weight form check'
        );

        # Check emergency form
        $expectedFile = self::TEST_DATA_DIR . 'dynamic_rules_emergency.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'emergency.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest emergency form check'
        );
    }

    public function testAutoGenIncludeCompleteFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form
        $expectedFile = self::TEST_DATA_DIR
            . 'dynamic_rules_registration_incl_complete.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest registration form check - include complete'
        );

        # Check registration label view
        $expectedFile = self::TEST_DATA_DIR
            . 'dynamic_rules_registration_label_view_incl_complete.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'registration_label_view.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest registration label view check - include complete'
        );

        # Check weight form
        $expectedFile = self::TEST_DATA_DIR
            . 'dynamic_rules_weight_incl_complete.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'weight.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest weight form check - include complete'
        );

        # Check emergency form
        $expectedFile = self::TEST_DATA_DIR
            . 'dynamic_rules_emergency_incl_complete.csv';
        $e = fopen($expectedFile, 'r');
        if ($e) {
            $expectedData = fread($e, filesize($expectedFile));
            fclose($e);
        }

        $actualFile = self::OUTPUT_DIR . 'emergency.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualData = fread($a, filesize($expectedFile));
            fclose($a);
        }
        
        $this->assertEquals($expectedData,
            $actualData,
            'dynamicRulesTest emergency form check - include complete'
        );
    }

    public function testAutoGenIncludeDagFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form header to make sure data access group
        # column is there
        $expected = '"registration_id","record_id","redcap_data_access_group",';
        $expected .= '"registration_date","first_name","last_name",';
        $expected .= '"address","phone","email","dob","ethnicity",';
        $expected .= '"race___0","race___1","race___2","race___3","race___4",';
        $expected .= '"race___5","sex","physician_approval","diabetic",';
        $expected .= '"diabetes_type","comments"';
        $expected .= "\n";

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualRegistrationHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualRegistrationHeader,
            'dynamicRulesTest registration form check - include dag'
        );

        # Check registration label view header to make sure data access group
        # column is there
        $actualFile = self::OUTPUT_DIR . 'registration_label_view.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualLabelViewHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualLabelViewHeader,
            'dynamicRulesTest registration form check - include dag'
        );

        # Check weight form header to make sure data access group
        # column is there
        $expectedWeightHeader = '"weight_id","registration_id","record_id",';
        $expectedWeightHeader .= '"redcap_repeat_instrument",';
        $expectedWeightHeader .=
            '"redcap_repeat_instance","redcap_data_access_group",';
        $expectedWeightHeader .= '"weight_time","weight_kg","height_m",';
        $expectedWeightHeader .= '"bmi","weekly_activity_level"';
        $expectedWeightHeader .= "\n";

        $actualFile = self::OUTPUT_DIR . 'weight.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualWeightHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expectedWeightHeader,
            $actualWeightHeader,
            'dynamicRulesTest weight form check - include dag'
        );

        # Check emergency form header to make sure data access group
        # column is there
        $expectedEmergencyHeader = '"emergency_id","record_id",';
        $expectedEmergencyHeader .= '"redcap_data_access_group","contact_name",';
        $expectedEmergencyHeader .= '"contact_address","contact_email",';
        $expectedEmergencyHeader .= '"contact_phone"';
        $expectedEmergencyHeader .= "\n";

        $actualFile = self::OUTPUT_DIR . 'emergency.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualEmergencyHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expectedEmergencyHeader,
            $actualEmergencyHeader,
            'dynamicRulesTest emergency form check - include dag'
        );
    }

    public function testAutoGenIncludeFileFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form to see if consent_form column is present
        $expected = '"registration_id","record_id","registration_date",';
        $expected .= '"first_name","last_name","address","phone",';
        $expected .= '"email","dob","ethnicity","race___0","race___1",';
        $expected .= '"race___2","race___3","race___4","race___5",';
        $expected .= '"sex","physician_approval","diabetic",';
        $expected .= '"diabetes_type","consent_form","comments"';
        $expected .= "\n";

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualHeader,
            'dynamicRulesTest registration form check - include file'
        );
    }

    public function testAutoGenRemoveNotesFields()
    {
        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form to see if address and comments columns
        # not included (both are notes fields)
        $expected = '"registration_id","record_id","registration_date",';
        $expected .= '"first_name","last_name","phone",';
        $expected .= '"email","dob","ethnicity","race___0","race___1",';
        $expected .= '"race___2","race___3","race___4","race___5",';
        $expected .= '"sex","physician_approval","diabetic",';
        $expected .= '"diabetes_type"';
        $expected .= "\n";

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualHeader,
            'dynamicRulesTest registration form check - remove notes fields'
        );
    }

    public function testAutoGenRemoveIdentifierFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check registration form to see if all identifier fields have been removed
        $expected = '"registration_id","record_id","registration_date",';
        $expected .= '"ethnicity","race___0","race___1",';
        $expected .= '"race___2","race___3","race___4","race___5",';
        $expected .= '"sex","physician_approval","diabetic",';
        $expected .= '"diabetes_type","comments"';
        $expected .= "\n";

        $actualFile = self::OUTPUT_DIR . 'registration.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualHeader,
            'dynamicRulesTest registration form check - remove notes fields'
        );
    }

    public function testAutoGenCombineNonRepeatingFields()
    {
        self::deleteOldResultsFiles();

        try {
            $properties = self::$config->getProperties();
            $properties[ConfigProperties::TRANSFORM_RULES_SOURCE] = Configuration::TRANSFORM_RULES_DEFAULT;
            $properties[ConfigProperties::TRANSFORM_RULES_TEXT] = '';

            $properties[ConfigProperties::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_DAG_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_INCLUDE_FILE_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_NOTES_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = 'false';
            $properties[ConfigProperties::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = 'true';
            $properties[ConfigProperties::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = 'combine_non_repeating';

            $redCapEtl = new RedCapEtl(self::$logger, $properties);
            $redCapEtl->run();
        } catch (EtlException $exception) {
            self::$logger->logException($exception);
            self::$logger->log('Processing failed.');
        }

        # Check file with all non-repeating fields in it
        $expected = '"combine_non_repeating_id","record_id","registration_date",';
        $expected .= '"first_name","last_name","address","phone",';
        $expected .= '"email","dob","ethnicity","race___0","race___1",';
        $expected .= '"race___2","race___3","race___4","race___5",';
        $expected .= '"sex","physician_approval","diabetic","diabetes_type",';
        $expected .= '"comments","contact_name","contact_address",';
        $expected .= '"contact_email","contact_phone"';
        $expected .= "\n";

        $actualFile = self::OUTPUT_DIR . 'combine_non_repeating.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualHeader,
            'dynamicRulesTest form check - combine non repeating fields'
        );

        # Check label view file with all non-repeating fields in it
        $actualFile = self::OUTPUT_DIR . 'combine_non_repeating_label_view.csv';
        $a = fopen($actualFile, 'r');
        if ($a) {
            $actualHeader = fgets($a);
            fclose($a);
        }
        
        $this->assertEquals($expected,
            $actualHeader,
            'dynamicRulesTest form check - combine non repeating fields label view'
        );

        # Check to make sure that the form with repeating fields has its own file
        $actualFile = self::OUTPUT_DIR . 'weight.csv';        
        $this->assertFileExists($actualFile,
            'dynamicRulesTest form check - combine non repeating fields repeating table'
        );

    }


}