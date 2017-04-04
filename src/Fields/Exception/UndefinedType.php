<?php

namespace Techart\Fields\Exception;

class UndefinedType extends \Techart\Fields\Exception
{
    public function __construct($s)
    {
        return parent::__construct("Undefined type: {$s}");
    }
}