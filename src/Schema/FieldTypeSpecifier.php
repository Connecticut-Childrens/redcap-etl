<?php

namespace IU\REDCapETL\Schema;

use IU\REDCapEtl\EtlException;

/**
 * Contains a complete type specification for a field, including the
 * type and size of the field.
 */
class FieldTypeSpecifier
{
    private $type;  // FieldType
    private $size;  // int

    /**
     * Constructor.
     *
     * @param string $type the type (name) for the field.
     * @param int $size the size of the field (for field types that have sizes).
     *
     * @see FieldType
     */
    public function __construct($type, $size)
    {
        $this->type = $type;
        $this->size = $size;
    }
    
    /**
     * Creates a field type specifier from the specified field type definition
     * string.
     *
     * @param string $fieldTypeDefinition a field type definition string,
     *     e.g., "int", "char(10)", "varchar(255)".
     *
     * @return FieldTypeSpecifier type specification that corresponds to
     *     the specified type definition.
     */
    public static function create($fieldTypeDefinition)
    {
        $fieldTypeSpecifier = null;
        
        if ($fieldTypeDefinition == null) {
            throw new EtlException("Missing field type definition.", EtlException::INPUT_ERROR);
        } elseif (!is_string($fieldTypeDefinition)) {
            throw new EtlException("Non-string field type definition.", EtlException::INPUT_ERROR);
        }
        
        $fieldTypeDefinition = trim($fieldTypeDefinition);
        if ($fieldTypeDefinition === '') {
            throw new EtlException("Missing field type definition.", EtlException::INPUT_ERROR);
        }
        
        # If the type definition has the syntax <type>(<type-size>)
        # for example: "varchar(100)"
        if (preg_match('/([a-zA-Z]+)\(([0-9]+)\)/', $fieldTypeDefinition, $matches) === 1) {
            $dbFieldType = $matches[1];
            $dbFieldSize = intval($matches[2]);
        } else {
            $dbFieldType = $fieldTypeDefinition;
            $dbFieldSize = null;
        }
                
        if (!FieldType::isValid($dbFieldType)) {
            $error = 'Invalid field type "'.$dbFieldType.'.';
            throw new EtlException($error, EtlException::INPUT_ERROR);
        }
        
        $fieldTypeSpecifier = new FieldTypeSpecifier($dbFieldType, $dbFieldSize);

        return $fieldTypeSpecifier;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getSize()
    {
        return $this->size;
    }
}
