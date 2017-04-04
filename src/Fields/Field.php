<?php

namespace Techart\Fields;

use Illuminate\Database\Schema\Blueprint;

/**
 * Class Field
 * @package Techart\Fields
 */
abstract class Field
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var array
     */
    public $data;
    /**
     * @var \Techart\Fields\Model
     */
    public $item;
    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $params;

    /**
     * @param Blueprint $table
     */
    public function createField(Blueprint $table)
    {

    }

    /**
     * @param Blueprint $table
     */
    public function checkSchema(Blueprint $table)
    {
        if (!$this->item->hasColumn($this->name)) {
            $this->createField($table);
        } else {
            $f = $this->createField($table);
            if ($f) {
                $f->change();
            }
        }
        return $this;
    }

    /**
     * @param Blueprint $table
     */
    public function checkIndexes(Blueprint $table)
    {
        $index = false;
        foreach(['index', 'unique'] as $type) {
            if (isset($this->params[$type])) {
                $index = $this->params[$type];
            }
        }
        if ($index) {
            $type = $index['name'];
            $name = $index['extra']? $index['extra'] : ('idx_'.$this->item->getTable().'_'.$this->name);
            $columns = $index['args'] ? $index['args'] : array($this->name);

            $info = $this->item->getIndexInfo($name);
            if (!$info) {
                $table->$type($columns, $name);
            } else {
                $currentType = $info->isUnique()? 'Unique' : 'Index';
                $currentTypeString = strtolower($currentType).':'.implode(',', $info->getColumns());
                $newTypeString = $type . ':'. implode(',', $columns);
                if ($newTypeString != $currentTypeString) {
                    $dropMethod = "drop{$currentType}";
                    $table->$dropMethod($name);
                    $table->$type($columns, $name);
                }
            }
        }
        return $this;
    }

    public function typeParamsExtra()
    {
        return $this->params['type']['extra'];
    }

    public function typeParamsArgs()
    {
        return $this->params['type']['args'];
    }

    public function typeParamsIntArg($default = 0)
    {
        $args = $this->typeParamsArgs();
        if (is_array($args)) {
            foreach ($args as $arg) {
                if (preg_match('{^\d+$}', $arg)) {
                    return (int)$arg;
                }
            }
        }
        return $default;
    }

    public function typeParamsEnumArg(array $enum, $default = false)
    {
        $args = $this->typeParamsArgs();
        if (is_array($args)) {
            foreach ($args as $arg) {
                $arg = strtolower($arg);
                if (in_array($arg, $enum)) {
                    return $arg;
                }
            }
        }
        return $default;
    }
}