<?php

namespace IU\REDCapETL\Rules;

class Rules
{
    /** @var array an array of Rule objects */
    private $rules;

    private $parsedLineCount;

    public function __construct()
    {
        $this->rules = array();
        $this->parsedLineCount = 0;
    }

    public function addRule($rule)
    {
        array_push($this->rules, $rule);
        if (!$rule->hasErrors()) {
            $this->parsedLineCount++;
        }
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getParsedLineCount()
    {
        return $this->parsedLineCount;
    }
}