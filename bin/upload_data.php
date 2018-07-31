#!/usr/bin/env php
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

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCap;

$apiUrl = 'https://abhmalat_redcap.abitc-redcap-container.uits.iu.edu/api/';
$apiToken  = '273424CC67263B849E41CCD2134F37C3';

$project = new RedCapProject($apiUrl, $apiToken);

# Print the project title
$projectInfo = $project->exportProjectInfo();
print "project title: ".$projectInfo['project_title']."\n";


$records = FileUtil::fileToString('data.csv');
$number = $project->importRecords($records, 'csv');
print "{$number} records were imported.\n";
