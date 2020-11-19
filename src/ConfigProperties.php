<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

/**
 * Configuration properties class.
 */
class ConfigProperties
{
    #----------------------------------------------------------------
    # Configuration properties
    #----------------------------------------------------------------
    const AUTOGEN_INCLUDE_COMPLETE_FIELDS      = 'autogen_include_complete_fields';
    const AUTOGEN_INCLUDE_DAG_FIELDS           = 'autogen_include_dag_fields';
    const AUTOGEN_INCLUDE_FILE_FIELDS          = 'autogen_include_file_fields';
    const AUTOGEN_INCLUDE_SURVEY_FIELDS        = 'autogen_include_survey_fields';
    const AUTOGEN_REMOVE_NOTES_FIELDS          = 'autogen_remove_notes_fields';
    const AUTOGEN_REMOVE_IDENTIFIER_FIELDS     = 'autogen_remove_identifier_fields';
    const AUTOGEN_COMBINE_NON_REPEATING_FIELDS = 'autogen_combine_non_repeating_fields';
    const AUTOGEN_NON_REPEATING_FIELDS_TABLE   = 'autogen_non_repeating_fields_table';

    const BATCH_SIZE             = 'batch_size';
    
    const CA_CERT_FILE              = 'ca_cert_file';
    const CALC_FIELD_IGNORE_PATTERN = 'calc_field_ignore_pattern';
    const CONFIG_NAME               = 'config_name';  # Name of configuration, if from the external module
    const CONFIG_OWNER              = 'config_owner'; # REDCap user who created the configuration,
                                                      #  if from the external module

    const TASK_CONFIG_FILE          = 'task_config_file'; # For including task configuration file;
                                                          # only allowed in workflows;

    const CREATE_LOOKUP_TABLE       = 'create_lookup_table';  # true/false indicating if a lookup table
                                                              # should be created
    const CRON_JOB                  = 'cron_job'; # true/false indicating if configuration file being run as cron job
    
    const DATA_SOURCE_API_TOKEN  = 'data_source_api_token';
    const DB_CONNECTION          = 'db_connection';
    const DB_SSL                 = 'db_ssl';
    const DB_SSL_VERIFY          = 'db_ssl_verify';

    const DB_PRIMARY_KEYS        = 'db_primary_keys';
    const DB_FOREIGN_KEYS        = 'db_foreign_keys';

    const DB_LOGGING             = 'db_logging';
    const DB_LOG_TABLE           = 'db_log_table';
    const DB_EVENT_LOG_TABLE     = 'db_event_log_table';

    const EMAIL_ERRORS           = 'email_errors';   # true/false indicating if errors should be logged by e-mail
    const EMAIL_SUMMARY          = 'email_summary';  # true/false indicating if email summary should be sent
    const EMAIL_FROM_ADDRESS     = 'email_from_address';
    const EMAIL_SUBJECT          = 'email_subject';
    const EMAIL_TO_LIST          = 'email_to_list';
    
    const EXTRACTED_RECORD_COUNT_CHECK = 'extracted_record_count_check';

    #---------------------------------------------------------------------------------------
    # Configuration properties for field types for fields that are generated by REDCap-ETL,
    # i.e., not specified by the user.
    #---------------------------------------------------------------------------------------
    const GENERATED_INSTANCE_TYPE  = 'generated_instance_type';   # for redcap_repeat_instance
    const GENERATED_KEY_TYPE       = 'generated_key_type';        # for primary and foreign keys
    const GENERATED_LABEL_TYPE     = 'generated_label_type';      # for label fields in label views
    const GENERATED_NAME_TYPE      = 'generated_name_type';       # for redcap_event_name, redcap_repeat_instrument
    const GENERATED_RECORD_ID_TYPE = 'generated_record_id_type';  # for generated REDCap record ID field
    const GENERATED_SUFFIX_TYPE    = 'generated_suffix_type';     # for redcap_suffix fields

    const IGNORE_EMPTY_INCOMPLETE_FORMS = 'ignore_empty_incomplete_forms';

    const LABEL_VIEW_SUFFIX        = 'label_view_suffix';
    const LOG_FILE                 = 'log_file';
    const LOOKUP_TABLE_NAME        = 'lookup_table_name';

    const PRE_PROCESSING_SQL       = 'pre_processing_sql';
    const PRE_PROCESSING_SQL_FILE  = 'pre_processing_sql_file';
    const POST_PROCESSING_SQL      = 'post_processing_sql';
    const POST_PROCESSING_SQL_FILE = 'post_processing_sql_file';
    
    const PRINT_LOGGING            = 'print_logging';   # true/false indicates if log messages should be printed
    const PROJECT_ID               = 'project_id';  # optional ID of REDCap project from which data are being extracted
    const REDCAP_API_URL             = 'redcap_api_url';

    const REDCAP_METADATA_TABLE      = 'redcap_metadata_table';
    const REDCAP_PROJECT_INFO_TABLE  = 'redcap_project_info_table';

    const SSL_VERIFY             = 'ssl_verify';
    
    const TABLE_PREFIX           = 'table_prefix';
    const TIME_LIMIT             = 'time_limit';
    const TIMEZONE               = 'timezone';

    const TRANSFORM_RULES_CHECK  = 'transform_rules_check';
    const TRANSFORM_RULES_FILE   = 'transform_rules_file';
    const TRANSFORM_RULES_SOURCE = 'transform_rules_source';
    const TRANSFORM_RULES_TEXT   = 'transform_rules_text';

    /**
     * Indicates if the specified property is a valid configuration
     * property.
     *
     * @param string $property the property to check for validity.
     *
     * @return boolean true if the specified property is valid, and
     *     false otherwise.
     */
    public static function isValid($property)
    {
        $isValid = false;

        if ($property != null) {
            $property = trim($property);
            
            $properties = self::getProperties();

            foreach ($properties as $name => $value) {
                if ($property === $value) {
                    $isValid = true;
                    break;
                }
            }
        }
        return $isValid;
    }


    /**
     * Indicates if the specified property is a property
     * that specifies (the path of) a file.
     *
     * @param string $property the propery to check.
     *
     * @return boolean true if the property is a property that
     *     specifies a file, and false otherwise.
     */
    public static function isFileProperty($property)
    {
        $isFile = false;
        switch ($property) {
            case self::CA_CERT_FILE:                # all these
            case self::LOG_FILE:                    # cases
            case self::PRE_PROCESSING_SQL_FILE:     # are
            case self::POST_PROCESSING_SQL_FILE:    # file
            case self::TASK_CONFIG_FILE:            # property
            case self::TRANSFORM_RULES_FILE:        # names
                $isFile = true;
                break;
            default:
                break;
        }
        return $isFile;
    }

    /**
     * Gets the property names and values.
     *
     * @return array a map from property name to property value for
     *     all the configuration properties.
     */
    public static function getProperties()
    {
        $reflection = new \ReflectionClass(self::class);
        $properties = $reflection->getConstants();
        return $properties;
    }
}
