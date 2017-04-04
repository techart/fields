<?php

namespace Techart\Fields\Type;

use Illuminate\Database\Schema\Blueprint;
use Techart\Fields\Field;

class Text extends StringField
{
    public function createField(Blueprint $table)
    {
        $size = $this->typeParamsEnumArg(array('medium', 'long'));
        $method = $size? "{$size}Text" : 'text';
        return $table->$method($this->name);
    }
}
