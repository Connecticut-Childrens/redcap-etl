<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

class FieldRule extends Rule
{
    public $redCapFieldName;
    public $dbFieldType;
    public $dbFieldSize;
    public $dbFieldName;  # database field name, specified
                          # if different from REDCap field name

    public function __construct($line, $lineNumber)
    {
        parent::__construct($line, $lineNumber);
    }
}
