<?php

namespace Techart\Fields\Type;

use Illuminate\Database\Schema\Blueprint;
use Techart\Fields\Field;

class StringField extends Field
{
    public function createField(Blueprint $table)
    {
        $len = $this->typeParamsIntArg(250);
        return $table->string($this->name, $len);
    }
}
