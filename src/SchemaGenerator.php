<?php

namespace IU\REDCapETL;

use IU\REDCapETL\Rules\FieldRule;
use IU\REDCapETL\Rules\Rule;
use IU\REDCapETL\Rules\TableRule;

use IU\REDCapETL\Schema\Field;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\RowsType;
use IU\REDCapETL\Schema\Schema;
use IU\REDCapETL\Schema\Table;

use IU\REDCapETL\Database\DBConnectFactory;

/**
 * Transformation rules used for transforming data from
 * the extracted format to the load format used in the
 * target database.
 */
class SchemaGenerator
{
    # Suffix for the REDCap field indicated the form has been completed
    const FORM_COMPLETE_SUFFIX = '_complete';

    # Parse status
    const PARSE_VALID = 'valid';
    const PARSE_ERROR = 'error';
    const PARSE_WARN  = 'warn';


    private $rules;

    private $lookupChoices;
    private $lookupTable;
    private $lookupTableIn;

    private $dataProject;
    private $logger;
    private $tablePrefix;

    /**
     * Constructor.
     *
     * @param string $rulesText the transformation rules text
     */
    public function __construct($dataProject, $tablePrefix, $logger)
    {
        $this->dataProject = $dataProject;
        $this->tablePrefix = $tablePrefix;
        $this->logger      = $logger;
    }


    public function generateSchema($rulesText)
    {
        $recordIdFieldName = $this->dataProject->getRecordIdFieldName();

        $fieldNames        = $this->dataProject->getFieldNames();

        $redCapFields      = $this->dataProject->getFieldNames();

        // Remove each instrument's completion field from the field list
        foreach ($redCapFields as $fieldName => $val) {
            if (preg_match('/'.self::FORM_COMPLETE_SUFFIX.'$/', $fieldName)) {
                unset($redCapFields[$fieldName]);
            }
        }

        $this->lookupChoices = $this->dataProject->getLookupChoices();
        $this->lookupTable = new LookupTable($this->lookupChoices, $this->tablePrefix);

        $info = '';
        $warnings = '';
        $errors = '';

        $schema = new Schema();

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." fields in REDCap.";
        $this->logger->logInfo($msg);
        $info .= $msg."\n";

        $table = null;
        
        $rulesParser = new RulesParser();
        $parsedRules = $rulesParser->parse($rulesText);
        
        # Add errors from parsing to errors string
        foreach ($parsedRules->getRules() as $rule) {
            foreach ($rule->getErrors() as $error) {
                $errors .= $error."\n";
            }
        }
            
        // Process each rule from first to last
        foreach ($parsedRules->getRules() as $rule) {
            if ($rule->hasErrors()) {
                // log the parse errors for this rule
                foreach ($rule->getErrors() as $error) {
                    $this->log($error);
                }
            } elseif ($rule instanceof TableRule) {
                #------------------------------------------------------------
                # Get the parent table - this will be:
                #   a Table object for non-root tables
                #   a string that is the primary key for root tables
                #------------------------------------------------------------
                $parentTableName = $this->tablePrefix . $rule->parentTable;
                $parentTable = $schema->getTable($parentTableName);

                $table = $this->generateTable($rule, $parentTable, $this->tablePrefix, $recordIdFieldName);

                $schema->addTable($table);

                #----------------------------------------------------------------------------
                # If the "parent table" is actually a table (i.e., this is a child
                # table and not a root table)
                #----------------------------------------------------------------------------
                if (is_a($parentTable, Table::class)) {
                    $table->setForeign($parentTable);  # Add a foreign key
                    $parentTable->addChild($table);    # Add as a child of parent table
                }
            } elseif ($rule instanceof FieldRule) {
                if ($table == null) {
                    break; // table not set, probably error with table rule
                    // Actually this should be flagged as an error
                }
                
                #------------------------------------------------------------
                # Create the needed fields
                #------------------------------------------------------------
                $fieldName = $rule->redCapFieldName;
                $fieldType = $rule->dbFieldType;
                $fieldSize = $rule->dbFieldSize;
                $dbFieldName = $rule->dbFieldName;

                $fields = array();
                
                // If this is a checkbox field
                if ($fieldType === FieldType::CHECKBOX) {
                    # For a checkbox in a Suffix table, append a valid suffix to
                    # the field name
                    if (($table->rowsType === RowsType::BY_SUFFIXES)
                            || ($table->rowsType === RowsType::BY_EVENTS_SUFFIXES)) {
                        # Lookup the choices using any one of the valid suffixes,
                        # since, for the same base field,  they all should be the same
                        $suffixes = $table->getPossibleSuffixes();
                        $lookupFieldName = $fieldName.$suffixes[0];
                    } else {
                        $lookupFieldName = $fieldName;
                    }

                    # Process each value of the checkbox
                    foreach ($this->lookupChoices[$lookupFieldName] as $value => $label) {
                        // Form the field names for this value
                        $checkBoxFieldName = $fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                        $checkBoxDbFieldName = '';
                        if (!empty($dbFieldName)) {
                            $checkBoxDbFieldName = $dbFieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value;
                        }

                        $field = new Field($checkBoxFieldName, FieldType::INT, null, $checkBoxDbFieldName);
                        $fields[$fieldName.RedCapEtl::CHECKBOX_SEPARATOR.$value] = $field;
                    }
                } else {
                    // Process a single field
                    $field = new Field($fieldName, $fieldType, $fieldSize, $dbFieldName);
                    $fields[$fieldName] = $field;
                }

                #-----------------------------------------------------------
                # Process each field
                #
                # Note: there can be more than one field, because a single
                # checkbox REDCap field may be stored as multiple database
                # fields (one for each option)
                #------------------------------------------------------------
                foreach ($fields as $fname => $field) {
                    $ftype = $field->type;
                    $fsize = $field->size;
                    $fdbname = $field->dbName;

                    //-------------------------------------------------------------
                    // !SUFFIXES: Prep for and warn that map field is not in REDCap
                    //-------------------------------------------------------------
                    if ((RowsType::BY_SUFFIXES !== $table->rowsType) &&
                            (RowsType::BY_EVENTS_SUFFIXES !== $table->rowsType) &&
                            $fname !== 'redcap_data_access_group' &&
                            (empty($fieldNames[$fname]))) {
                        $msg = "Field not found in REDCap: '".$fname."'";
                        $this->logger->logInfo($msg);
                        $warnings .= $msg."\n";
                        continue 2; //continue 3;
                    }


                    //------------------------------------------------------------
                    // SUFFIXES: Prep for warning that map field is not in REDCap
                    //           Prep for warning that REDCap field is not in Map
                    //------------------------------------------------------------

                    // For fields in a SUFFIXES table, use the possible suffixes,
                    // including looking up the tree of parent tables, to look
                    // for at least one matching field in the exportfieldnames
                    if ((RowsType::BY_SUFFIXES === $table->rowsType)
                             || (RowsType::BY_EVENTS_SUFFIXES === $table->rowsType)) {
                        $possibles = $table->getPossibleSuffixes();

                        $fieldFound = false;

                        // Foreach possible suffix, is the field found?
                        foreach ($possibles as $sx) {
                            // In case this is a checkbox field
                            if ($fieldType === FieldType::CHECKBOX) {
                                // Separate root from category
                                list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                                // Form the exported field name
                                $exportFname = $rootName.$sx.RedCapEtl::CHECKBOX_SEPARATOR.$category;

                                // Form the metadata field name
                                // Checkbox fields have a single metadata field name, but
                                // (usually) multiple exported field names
                                $metaFname = $rootName.$sx;
                            } else {
                                // Otherwise, just append suffix
                                $exportFname = $fname.$sx;
                                $metaFname = $fname.$sx;
                            }

                            //--------------------------------------------------------------
                            // SUFFIXES: Remove from warning that REDCap field is not in Map
                            //--------------------------------------------------------------
                            if (!empty($fieldNames[$exportFname])) {
                                $fieldFound = true;
                                 // Remove this field from the list of fields to be mapped
                                unset($redCapFields[$exportFname]);
                            }
                        } // Foreach possible suffix

                        //------------------------------------------------------------
                        // SUFFIXES: Warn that map field is not in REDCap
                        //------------------------------------------------------------
                        if (false === $fieldFound) {
                            $msg = "Suffix field not found in REDCap: '".$fname."'";
                            $this->log($msg);
                            $warnings .= $msg."\n";
                            break; // continue 2;
                        }
                    } else {
                        //------------------------------------------------------------
                        // !SUFFIXES: Prep for warning that REDCap field is not in Map
                        //------------------------------------------------------------

                        // Not BY_SUFFIXES, and field was found

                        // In case this is a checkbox field
                        if ($fieldType === FieldType::CHECKBOX) {
                            // Separate root from category
                            list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fname);

                            // Form the metadata field name
                            // Checkbox fields have a single metadata field name, but
                            // (usually) multiple exported field names
                            $metaFname = $rootName;
                        } else {
                            // $metaFname is redundant here, but used later when
                            // deciding whether or not to create rows in Lookup
                            $metaFname = $fname;
                        }

                        //---------------------------------------------------------------
                        // !SUFFIXES: Remove from warning that REDCap field is not in Map
                        //---------------------------------------------------------------

                        // Remove this field from the list of fields to be mapped
                        unset($redCapFields[$fname]);
                    }
        
                    #-----------------------------------------------------------------
                    # If the field name is the record ID field name, don't process
                    # it, because it should have already been automatically added
                    #-----------------------------------------------------------------
                    if ($fname !== $recordIdFieldName) {
                        // Create a new Field
                        $field = new Field($fname, $ftype, $fsize, $fdbname);

                        // Add Field to current Table (error if no current table)
                        $table->addField($field);

                        // If this field has category/label choices
                        if (array_key_exists($metaFname, $this->lookupChoices)) {
                            $this->lookupTable->addLookupField($table->name, $metaFname);
                            $field->usesLookup = $metaFname;
                            $table->usesLookup = true;
                        }
                    }
                } // End foreach field to be created
            } // End if for rule types
        } // End foreach
        
        
        if ($parsedRules->getParsedLineCount() < 1) {
            $msg = "Found no lines in Schema Map";
            $this->log($msg);
            $errors .= $msg."\n";
        }

        // Log how many fields in REDCap could be parsed
        $msg = "Found ".count($redCapFields)." unmapped fields in REDCap.";
        $this->logger->logInfo($msg);

        // Set warning if count of remaining redcap fields is above zero
        if (0 < count($redCapFields)) {
            $warnings .= $msg."\n";

            // List fields, if count is five or less
            if (10 > count($redCapFields)) {
                $msg = "Unmapped fields: ".  implode(', ', array_keys($redCapFields));
                $this->logger->logInfo($msg);
                $warnings .= $msg;
            }
        }

        $messages = array();
        if ('' !== $errors) {
            $messages = array(self::PARSE_ERROR,$errors.$info.$warnings);
        } elseif ('' !== $warnings) {
            $messages = array(self::PARSE_WARN,$info.$warnings);
        } else {
            $messages = array(self::PARSE_VALID,$info);
        }

        return array($schema, $this->lookupTable, $messages);
    }


    public function generateTable($rule, $parentTable, $tablePrefix, $recordIdFieldName)
    {
        $tableName = $this->tablePrefix . $rule->tableName;
        $rowsType  = $rule->rowsType;

        # Create the table
        $table = new Table(
            $tableName,
            $parentTable,
            $rowsType,
            $rule->suffixes,
            $recordIdFieldName
        );

        #---------------------------------------------------------
        # Add the record ID field as a field for all tables
        # (unless the primary key or foreign key has the same
        # name).
        #
        # Note that it looks like this really needs to be added
        # as a string type, because even if it is specified as
        # an Integer in REDCap, there will be no length
        # restriction (unless a min and max are explicitly
        # specified), so a value can be entered that the
        # database will not be able to handle.
        #---------------------------------------------------------
        if ($table->primary === $recordIdFieldName) {
            $error = 'Primary key field has same name as REDCap record id "'
                .$recordIdFieldName.'" on line '
                .$rule->getLineNumber().': "'.$rule->getLine().'"';
            return table;   // try to fix
        } else {
            $field = new Field($recordIdFieldName, FieldType::STRING);
            $table->addField($field);
        }

        // Depending on type of table, add output fields to represent
        // which iteration of a field's value is stored in a row of
        // the table
        switch ($rowsType) {
            case RowsType::BY_EVENTS:
                $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                $table->addField($field);
                break;

            case RowsType::BY_REPEATING_INSTRUMENTS:
                $field = new Field(
                    RedCapEtl::COLUMN_REPEATING_INSTRUMENT,
                    RedCapEtl::COLUMN_REPEATING_INSTRUMENT_TYPE
                );
                $table->addField($field);
                $field = new Field(
                    RedCapEtl::COLUMN_REPEATING_INSTANCE,
                    RedCapEtl::COLUMN_REPEATING_INSTANCE_TYPE
                );
                $table->addField($field);
                break;

            case RowsType::BY_SUFFIXES:
                $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                $table->addField($field);
                break;

            case RowsType::BY_EVENTS_SUFFIXES:
                $field = new Field(RedCapEtl::COLUMN_EVENT, RedCapEtl::COLUMN_EVENT_TYPE);
                $table->addField($field);
                $field = new Field(RedCapEtl::COLUMN_SUFFIXES, RedCapEtl::COLUMN_SUFFIXES_TYPE);
                $table->addField($field);
                break;

            default:
                break;
        }

        return $table;
    }

    protected function log($message)
    {
        $this->logger->logInfo($message);
    }
}
