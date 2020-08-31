#!/usr/bin/env php
<?php

#==============================================================
# Script for setting up the automated REDCap-ETL tests.
#==============================================================

$configInitDir = __DIR__.'/../tests/config-init/';
$configDir     = __DIR__.'/../tests/config/';


#---------------------------------------------------------------------
# Configuration files (map from configuration file name to database)
#---------------------------------------------------------------------
$configFiles = [
    'basic-demography-2.ini'                           => '',
    'basic-demography-3.ini'                           => '',
    'basic-demography-bad-field-name.ini'              => '',
    'basic-demography-bad-rule.ini'                    => '',
    'basic-demography-duplicate-primary-key-name.ini'  => '',
    'basic-demography.ini'                             => '',
    'basic-demography.json'                            => '',
    'repeating-events.ini'                             => '',
    'repeating-events-mysql.ini'                       => 'mysql',
    'repeating-events-mysql-ssl.ini'                   => 'mysql-with-ssl',
    'repeating-events-postgresql.ini'                  => 'postgresql',
    'repeating-events-sqlite.ini'                      => 'sqlite',
    'repeating-events-sqlserver.ini'                   => 'sql-server',
    'repeating-events-sqlserver-ssl.ini'               => 'sql-server-with-ssl',
    'visits-empty-rules.ini'                           => 'mysql',
    'visits.ini'                                       => 'mysql',
    'visits-missing-suffix.ini'                        => '',
    'visits-sqlite.ini'                                => 'sqlite'
];

$rulesFiles = [
    'basic-demography-rules-badFieldName.txt',
    'basic-demography-rules-badRule.txt',
    'basic-demography-rules-duplicate-primary-key-name.txt',
    'basic-demography-rules.txt',
    'repeating-events-mysql-rules.txt',
    'repeating-events-rules.txt',
    'visits-empty-rules.txt',
    'visits-missing-suffix-rules.txt',
    'visits-rules.txt'
];

$sqlFiles = [
    'visits.sql'
];


#----------------------------------------------
# Parse the test setup configuration file
#----------------------------------------------
$properties = parse_ini_file(__DIR__.'/../tests/config.ini', true);

$basicDemographyApiUrl    = $properties['basic-demography']['redcap_api_url'];
$basicDemographyApiToken  = $properties['basic-demography']['data_source_api_token'];

$repeatingEventsApiUrl    = $properties['repeating-events']['redcap_api_url'];
$repeatingEventsApiToken  = $properties['repeating-events']['data_source_api_token'];

$visitsApiUrl    = $properties['visits']['redcap_api_url'];
$visitsApiToken  = $properties['visits']['data_source_api_token'];

$dbConnection['mysql']               = $properties['mysql']['db_connection'];
$dbConnection['mysql-with-ssl']      = $properties['mysql-with-ssl']['db_connection'];
$dbConnection['postgresql']          = $properties['postgresql']['db_connection'];
$dbConnection['sql-server']          = $properties['sql-server']['db_connection'];
$dbConnection['sql-server-with-ssl'] = $properties['sql-server-with-ssl']['db_connection'];

#-----------------------------------------------------------------------------------
# Copy the rules and SQL files that are not already in the test condig directory
#-----------------------------------------------------------------------------------
foreach (array_merge($rulesFiles, $sqlFiles) as $configFile) {
    $fromPath = realpath($configInitDir . $configFile);
    $toPath   = $configDir .$configFile;

    if (!file_exists($fromPath)) {
        print "ERROR - required configuration file \"{$fromPath}\" could not be found.\n";
        exit(1);
    }

    # If the copied file doesn't exist, copy it
    if (!file_exists($toPath)) {
        print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
        copy($fromPath, $toPath);
    }
}


#------------------------------------------------------------
# For each configuration file, update the API URL and token,
# and, where applicable, the database connection
#------------------------------------------------------------
foreach ($configFiles as $configFile => $db) {
    $fromPath = realpath($configInitDir . $configFile);
    $toPath   = $configDir . $configFile;

    if (!file_exists($fromPath)) {
        print "ERROR - required configuration file \"{$fromPath}\" could not be found.\n";
        exit(1);
    }

    $contents = file_get_contents($fromPath);

    if (empty($contents)) {
        print "ERROR - required configuration file \"{$fromPath}\" is empty.\n";
        exit(1);
    }

    # If the copied file doesn't exist, copy it
    if (!file_exists($toPath)) {
        # If the there is no database or the database is sqlite (which hard codes the
        # database connection) for this config file, or there is a database
        # and it has been set in the properties file
        if (empty($db) || $db === 'sqlite' || !empty($dbConnection[$db])) {
            print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
            copy($fromPath, $toPath);
        }
    } else {
        # The file does exist

        # If there is a database for this confile file, and its value has not been set,
        # delete the existing config file
        if (!empty($db) && $db !== 'sqlite' && empty($dbConnection[$db])) {
            print "Deleting config file \"{$configFile}\", because no \"{$db}\" property was set\n";
            unlink($toPath);
        } else {
            # Copy in case changes were made
            print "Copying file \"{$configFile}\" to \"{$configDir}\"\n";
            copy($fromPath, $toPath);
        }
    }

    if (file_exists($toPath)) {
        if (preg_match('/basic-demography-3.ini/', $toPath) === 1) {
            # Special case, test for properties not set
        } elseif (preg_match('/basic-demography.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Basic demography files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$basicDemographyApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$basicDemographyApiToken}",
                $contents
            );
        } elseif (preg_match('/basic-demography.*\.json/', $toPath) === 1) {
            #-------------------------------------
            # Basic demography JSON files
            #-------------------------------------
            #    "redcap_api_url": "",
            # "data_source_api_token": "",

            $contents = preg_replace(
                '/"redcap_api_url"\s*:.*/',
                '"redcap_api_url" : "'.$basicDemographyApiUrl.'",',
                $contents
            );
            $contents = preg_replace(
                '/"data_source_api_token"\s*:.*/',
                '"data_source_api_token" : "'.$basicDemographyApiToken.'",',
                $contents
            );
        } elseif (preg_match('/repeating-events.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Repeating events files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$repeatingEventsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$repeatingEventsApiToken}",
                $contents
            );
        } elseif (preg_match('/visits.*\.ini/', $toPath) === 1) {
            #-------------------------------------
            # Visits files
            #-------------------------------------
            $contents = preg_replace(
                '/redcap_api_url\s*=.*/',
                "redcap_api_url = {$visitsApiUrl}",
                $contents
            );
            $contents = preg_replace(
                '/data_source_api_token\s*=.*/',
                "data_source_api_token = {$visitsApiToken}",
                $contents
            );
        }

        if (!empty($db) && $db !== 'sqlite') {
            $contents = preg_replace(
                '/db_connection\s*=.*/',
                "db_connection = ".$dbConnection[$db],
                $contents
            );
        }
        print "Updating file \"{$toPath}\"\n";
        file_put_contents($toPath, $contents);
    }
}
