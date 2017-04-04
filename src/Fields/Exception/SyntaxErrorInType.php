<?php

namespace Techart\Fields\Exception;

class SyntaxErrorInType extends \Techart\Fields\Exception
{
    public function __construct($s)
    {
        return parent::__construct("Syntax error in field type: {$s}");
    }
}