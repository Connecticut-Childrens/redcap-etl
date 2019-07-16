<?php

namespace IU\REDCapETL\Database;

use PHPUnit\Framework\TestCase;

#use IU\REDCapETL\RedCapEtl;            
use IU\REDCapETL\Schema\RowsType;    
use IU\REDCapETL\Schema\Row;    
use IU\REDCapETL\Schema\FieldTypeSpecifier;    
use IU\REDCapETL\Schema\FieldType;    
use IU\REDCapETL\Schema\Field;    
use IU\REDCapETL\Schema\Table;    

/**
* PHPUnit tests for the CsvDbConnection class.
*/

class CsvDbConnectionTest extends TestCase
{
    protected $dbString = './tests/output/';
    protected $ssl = null;
    protected $sslVerify = null;
    protected $caCertFile = null;

    protected $suffixes = '';
    protected $rowsType = RowsType::ROOT;
    protected $recordIdFieldName = 'record_id';

    public function testConstructor()
    {
        $tablePrefix = 'testpre';
        $labelViewSuffix = 'testsuf';

        #test object creation
        $csvDbConnection = new CsvDbConnection($this->dbString, $this->ssl, $this->sslVerify, $this->caCertFile, $tablePrefix, $labelViewSuffix);
        $this->assertNotNull($csvDbConnection, 'Not null csvdbconnection');
    }


    public function testCreateTable()
    {
        #############################################################
        # create the table object with just column headings
        # that will be written to a csv file
        #############################################################

        $name = 'test';
        $parent = 'test_id';
        $keyType = new FieldTypeSpecifier(FieldType::INT, null);
        $rootTable = new Table($name, $parent, $keyType, array($this->rowsType), $this->suffixes, $this->recordIdFieldName);

        #### Create fields in the Table object
        
        #the first field will have a column-heading change in the csv file in which
        #the table column 'record_id' will appear as 'redcap_record_id' in the file.
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            'redcap_record_id'
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'full_name',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'weight',
            FieldType::INT,
            null
        );
        $rootTable->addField($field2);

        # Create the CsvDbConnection`
        $tablePrefix = null;
        $labelViewSuffix = null;
        $csvDbConnection = new CsvDbConnection($this->dbString, $this->ssl, $this->sslVerify, $this->caCertFile, $tablePrefix, $labelViewSuffix);


        #############################################################
        # Execute the tests for this method                         
        #############################################################
        # run the createTable method to create the file and verify it returns successfully
        $result = $csvDbConnection->createTable($rootTable, false);
        $expected = 1;
        $this->assertEquals($result, $expected, 'CsvDbConnection createTable successful return check');
        
        #Check to see if a file was actually created.     
        $newFile = $this->dbString . $rootTable->name . CsvDbConnection::FILE_EXTENSION;
        $this->assertFileExists($newFile, 'CsvDbConnection createTable new file exists check');

        #Verify header was created as expected.
        $expected = '"test_id","redcap_record_id","full_name","weight"' . chr(10);
        #$expected = '"test_id","record_id","full_name","weight"' . chr(10);
        $header = null;

        $fh = fopen($newFile, 'r');
        if ($fh) {
            $header = fgets($fh);
            fclose($fh);
        }

        $this->assertEquals($expected, $header, 'CsvDbConnection createTable header is correct check');
    }

   public function testInsertRows()
    {
        #############################################################
        # create the table object that will be written to a csv file
        #############################################################

        $name = 'registration';
        $parent = 'registration_id';
        $rowsType = RowsType::ROOT;
        $suffixes = '';
        $recordIdFieldName = 'record_id';

        $keyType = new FieldTypeSpecifier(FieldType::INT, null);

        $rootTable = new Table($name, $parent, $keyType, array($rowsType), $suffixes, $recordIdFieldName);

        # Create fields in the Table object
        $field0 = new Field(
            'record_id',
            FieldType::INT,
            null               
        );
        $rootTable->addField($field0);

        $field1 = new Field(
            'first_name',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field1);

        $field2 = new Field(
            'last_name',
            FieldType::STRING,
            null
        );
        $rootTable->addField($field2);

        $field3 = new Field(
            'weight',
            FieldType::INT,
            null               
        );
        $rootTable->addField($field3);

        # Insert two rows into the Table object
        $foreignKey = null;
        $suffix = null;
        $data1 = [
            'record_id' => 1000,
            'first_name' => 'Anahi',
            'last_name' => 'Gilason',
            'weight' => 77
        ];
        $rootTable->createRow($data1, $foreignKey, $suffix, RowsType::BY_EVENTS);


        $data2 = [
            'record_id' => 1001,
            'first_name' => 'Marianne',
            'last_name' => 'Crona',
            'weight' => 57
        ];
        $rootTable->createRow($data2, $foreignKey, $suffix, RowsType::BY_EVENTS);

        # Create the csv file
        $tablePrefix = null;
        $labelViewSuffix = null;
        $csvDbConnection = new CsvDbConnection($this->dbString, $this->ssl, $this->sslVerify, $this->caCertFile, $tablePrefix, $labelViewSuffix);
        $csvDbConnection->createTable($rootTable, false);
        
        # insert rows into the file
        $result = $csvDbConnection->insertRows($rootTable);
        $expected = 1;
        $this->assertEquals($result, $expected, 'CsvDbConnection insertRows successful return check');
       
        #Verify rows were written as expected.
        $expectedRows = [     
            '"registration_id","record_id","first_name","last_name","weight"' . chr(10),
            '1,1000,"Anahi","Gilason",77' . chr(10),
            '2,1001,"Marianne","Crona",57' . chr(10)
        ];
        $fileContentsOK = true;

        $newFile = $this->dbString . $rootTable->name . CsvDbConnection::FILE_EXTENSION;

        $i = 0;
        $lines = file($newFile);
        foreach($lines as $line) {
            if ($expectedRows[$i] !== $line) {
                $fileContentsOK = false;
            }
            $i++;
        }            

        $this->assertTrue($fileContentsOK, 'CsvDbConnection insertRows File Contents check');
    }

    public function testProcessQueryFile() {

        # Create the csvDbConnection object
        $tablePrefix = null;
        $labelViewSuffix = null;
        $csvDbConnection = new CsvDbConnection($this->dbString, $this->ssl, $this->sslVerify, $this->caCertFile, $tablePrefix, $labelViewSuffix);

        # execute tests for this method
        $queryFile = 'abc';
        $result = $csvDbConnection->processQueryFile($queryFile);
        $expected = 1;
        $this->assertEquals($result, $expected, 'CsvDbConnection processQueryFile check');

    }

}
