<?php

namespace Techart\Fields\Exception;

class UndefinedField extends \Techart\Fields\Exception
{
    public function __construct($f, $m)
    {
        return parent::__construct("Undefined field {$f} in model {$m}");
    }
}