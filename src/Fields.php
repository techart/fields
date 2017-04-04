<?php

namespace Techart;

use Techart\Core\Service;
use Techart\Fields\Exception\SyntaxErrorInType;
use Techart\Fields\Exception\UndefinedType;

class Fields extends Service
{
    protected $schemaWasUpdated = array();
    protected $parsedTypes = array();
    protected $classes = array('string' => 'StringField', 'Integer', 'Text', 'Checkbox');

    public function init()
    {
        $classes = $this->classes;
        $this->classes = array();
        foreach ($classes as $name => $class) {
            if (is_numeric($name)) {
                $name = strtolower($class);
            }
            $this->classes[$name] = "Techart.Fields.Type.{$class}";
        }
    }

    public function add($name, $class)
    {
        $this->classes[$name] = $class;
    }

    public function schemaWasUpdated($table)
    {
        return isset($this->schemaWasUpdated[$table]);
    }

    public function schemaUpdated($table)
    {
        $this->schemaWasUpdated[$table] = true;
    }

    public function getClass($type)
    {
        $type = strtolower(trim($type));
        $class = Core::config("fields:{$type}");

        if (is_null($class) && isset($this->classes[$type])) {
            $class = $this->classes[$type];
        }

        if (!$class) {
            throw new UndefinedType($type);
        }

        return $class;
    }

    public function create($name, $data, $item)
    {
        $typeSrc = isset($data['type']) ? $data['type'] : 'input';
        $typeParsed = $this->parseType($typeSrc);
        $type = $typeParsed['type']['name'];
        $class = $this->getClass($type);
        $field = Core::make($class);
        $field->type = $type;
        $field->name = $name;
        $field->data = $data;
        $field->item = $item;
        $field->params = $typeParsed;
        return $field;
    }

    public function parseType($in)
    {
        $orig = $in;
        if (!isset($this->parsedTypes[$in])) {
            $data = $this->parseTypeChunk($in);
            if (!$data) {
                throw new SyntaxErrorInType($orig);
            }
            $rc['type'] = $data;
            while ($data = $this->parseTypeChunk($in)) {
                $name = $data['name'];
                $rc[$name] = $data;
            }
            $this->parsedTypes[$in] = $rc;
        }
        return $this->parsedTypes[$in];
    }

    public function parseTypeChunk(&$in)
    {
        $in = trim($in);
        if ($in == '') {
            return false;
        }
        $name = false;
        $extra = false;
        $args = false;
        if ($m = Core::regexp('{^([a-z0-9:_-]+)\(([^()]*)\)(.*)$}i', $in)) {
            $name = $m[1];
            $args = trim($m[2]);
            $in = trim($m[3]);
        } elseif ($m = Core::regexp('{^([a-z0-9:_-]+)(.*)$}i', $in)) {
            $name = $m[1];
            $in = trim($m[2]);
        }
        if (!$name) {
            $in = '';
            return false;
        }
        if ($m = Core::regexp('{^(.+):(.+)$}', $name)) {
            $name = trim($m[1]);
            $extra = trim($m[2]);
        }
        if ($args) {
            $_args = explode(',', $args);
            $args = array();
            foreach ($_args as $arg) {
                $args[] = trim($arg);
            }
        }
        return array(
            'name' => $name,
            'extra' => $extra,
            'args' => $args,
        );
    }
}
