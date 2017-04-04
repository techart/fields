<?php

namespace Techart\Fields\Type;

use Illuminate\Database\Schema\Blueprint;
use Techart\Fields\Field;

class Integer extends Field
{
    public function createField(Blueprint $table)
    {
        $size = $this->typeParamsEnumArg(array('tiny','small', 'medium', 'big'));
        $unsigned = (bool)$this->typeParamsEnumArg(array('unsigned'));
        $method = $size? "{$size}Integer" : 'integer';
        return $table->$method($this->name, false, $unsigned);
    }
}
