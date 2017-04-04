<?php

namespace Techart\Fields;

use Illuminate\Database\Schema\Blueprint;
use Techart\Core;
use Techart\Fields;
use Rhumsaa\Uuid\Uuid;

/**
 * Class Model
 * @package Techart\Fields
 */
abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @var bool
     */
    public $incrementing = false;
    /**
     * @var string
     */
    protected $idType = 'uuid';
    /**
     * @var array
     */
    protected $fields = array();

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Core::db()->connectionNameFor($this->getTable());
        if ($this->idType == 'auto_increment') {
            $this->incrementing = true;
        }
        $this->updateSchemaIfNecessary();
    }

    /**
     * @return mixed
     */
    abstract public function fields();

    /**
     * @param $name
     * @return mixed
     * @throws Exception\UndefinedField
     */
    public function field($name)
    {
        if (!isset($this->fields[$name])) {
            $fields = $this->fields();
            if (!isset($fields[$name])) {
                throw new Fields\Exception\UndefinedField($name, get_class($this));
            }
            $this->fields[$name] = Core::fields()->create($name, $fields[$name], $this);
        }
        return $this->fields[$name];
    }

    /**
     * @param string $event
     * @param bool|true $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        $rc = null;
        switch ($event) {
            case 'saving':
                if (false === $this->beforeSave()) {
                    return false;
                }
                $this->immutableBeforeSave();
                break;
            case 'creating':
                if (false === $this->beforeInsert()) {
                    return false;
                }
                $this->immutableBeforeInsert();
                break;
            case 'updating':
                if (false === $this->beforeUpdate()) {
                    return false;
                }
                $this->immutableBeforeUpdate();
                break;
            case 'deleting':
                if (false === $this->beforeDelete()) {
                    return false;
                }
                $this->immutableBeforeDelete();
                break;
            case 'saved':
                $this->afterSave();
                $this->immutableAfterSave();
                break;
            case 'created':
                $this->afterInsert();
                $this->immutableAfterInsert();
                break;
            case 'updated':
                $this->afterUpdate();
                $this->immutableAfterUpdate();
                break;
        }
        return parent::fireModelEvent($event, $halt);
    }

    /**
     * @return $this
     */
    public function updateSchemaIfNecessary()
    {
        $table = $this->getTable();
        if (Core::fields()->schemaWasUpdated($table)) {
            return $this;
        }
        if (Core::cache()->classModified($this, false)) {
            $this->updateSchema();
        }
        Core::fields()->schemaUpdated($table);
    }

    /**
     * @return mixed
     */
    public function dbSchema()
    {
        return Core::db()->schema($this->getConnectionName());
    }

    /**
     * @param \Closure $closure
     * @return mixed
     */
    public function tableSchema(\Closure $closure)
    {
        return $this->dbSchema()->table($this->getTable(), $closure);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function hasColumn($name)
    {
        return $this->dbSchema()->hasColumn($this->getTable(), $name);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function hasColumns(array $columns)
    {
        return $this->dbSchema()->hasColumns($this->getTable(), $columns);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getColumnType($name)
    {
        return $this->dbSchema()->getColumnType($this->getTable(), $name);
    }

    public function getIndexInfo($name)
    {
        $indexes = $this->getConnection()->getDoctrineSchemaManager()->listTableIndexes($this->getTable());
        return isset($indexes[$name])? $indexes[$name] : null;
    }

    /**
     *
     */
    public function updateSchema()
    {
        $tableName = $this->getTable();
        if (!$this->dbSchema()->hasTable($tableName)) {
            $this->dbSchema()->create($tableName, function (Blueprint $table) {
                if ($this->idType == 'auto_increment') {
                    $table->increments($this->primaryKey);
                } elseif ($this->idType == 'uuid') {
                    $table->string($this->primaryKey, 36);
                    $table->primary($this->primaryKey);
                }
            });
        }
        $this->tableSchema(function (Blueprint $table) {
            if ($this->idType == 'auto_increment' && $this->getColumnType($this->primaryKey) == 'string') {
                $table->increments($this->primaryKey)->change();
            } elseif ($this->idType == 'uuid' && $this->getColumnType($this->primaryKey) == 'integer') {
                $table->string($this->primaryKey, 36)->change();
            }
            if ($this->timestamps && !$this->hasColumns(['created_at', 'updated_at'])) {
                $table->timestamps();
            }
            $this->checkFieldsSchema($table);
        });
    }

    /**
     * @param Blueprint $table
     * @return $this
     * @throws Exception\UndefinedField
     */
    public function checkFieldsSchema(Blueprint $table)
    {
        foreach ($this->fields() as $name => $data) {
            $field = $this->field($name);
            $field->checkSchema($table);
            $field->checkIndexes($table);
        }
        return $this;
    }

    /**
     * @return Uuid
     */
    public function generateNewId()
    {
        return Uuid::uuid4();
    }

    /**
     *
     */
    final public function immutableBeforeSave()
    {
    }

    /**
     *
     */
    final public function immutableAfterSave()
    {
    }

    /**
     *
     */
    final public function immutableBeforeInsert()
    {
        if ($this->idType == 'uuid') {
            $this->{$this->getKeyName()} = (string)$this->generateNewId();
        }
    }

    /**
     *
     */
    final public function immutableAfterInsert()
    {
    }

    /**
     *
     */
    final public function immutableBeforeUpdate()
    {
    }

    /**
     *
     */
    final public function immutableAfterUpdate()
    {
    }

    /**
     *
     */
    final public function immutableBeforeDelete()
    {
    }

    /**
     *
     */
    final public function immutableAfterDelete()
    {
    }

    /**
     *
     */
    public function beforeInsert()
    {

    }

    /**
     *
     */
    public function afterInsert()
    {

    }

    /**
     *
     */
    public function beforeSave()
    {

    }

    /**
     *
     */
    public function afterSave()
    {

    }

    /**
     *
     */
    public function beforeUpdate()
    {

    }

    /**
     *
     */
    public function afterUpdate()
    {

    }

    /**
     *
     */
    public function beforeDelete()
    {

    }

    /**
     *
     */
    public function afterDelete()
    {

    }

}