<?php



namespace Flame;

use mysqli;

class Model
{
    use Log;
    var $name; // use as alias
    /**
     * @Configuration for database
     **/
    public $useSchema = 'default';
    public $useTable;

    private $schema;
    private $type;
    private $host;
    private $port;
    private $username;
    private $password;
    private $prefix;

    private $connect;
    public $statement;
    public $params;
    public $queryType;
    //public $id;
    public $sort;
    public $limit;
    public $offset;
    public $groupBy;

    public $joins;

    public $fields = [];
    protected $mresult;
    public $ArithmeticOperators = array('+', '-', '*', '/', '%');
    public $BitwiseOperators = array('&', '|', '^', '~', '<<', '>>');
    public $ComparisonOperators = array('=', '>', '<', '>=', '<=', '<>', '!=', 'IS', 'LIKE', 'REGEXP', 'IN', 'BETWEEN');
    public $CompoundOperators = array('+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=', '<<=', '>>=');
    public $LogicalOperators = array('ALL', 'AND', 'ANY', 'BETWEEN', 'EXISTS', 'IN', 'LIKE', 'NOT', 'OR', 'SOME');
    public $controller;
    public $data;
    public function __construct(&$controller = null)
    {
        require_once APP . 'config/database.php';
        $config = get_defined_vars()[$this->useSchema];
        $this->host = $config['host'];
        $this->type = $config['type'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->prefix = $config['prefix'];
        $this->schema = $config['database'];
        $this->useTable = strtolower(Inflector::pluralize($this->name));

        $this->controller = new $controller;

        $this->data =  $this->controller->data;
        $this->_Connect();

        $this->listFields();
    }

    function _Connect()
    {
        $this->connect = new mysqli($this->host, $this->username, $this->password, $this->schema, $this->port);
        if ($this->connect->connect_error) {
            var_dump($this->connect->connect_error);
        }
        return $this;
    }

    function listFields()
    {
        $this->statement = "DESCRIBE {$this->useTable}";
        $result = mysqli_query($this->connect, $this->statement);
        while ($r = mysqli_fetch_assoc($result)) {
            $this->{$r['Field']} = null;
        }
        return  $this;
    }

    function create()
    {
        $this->log(print_r($this, true));
        return $this;
    }

    function save()
    {
    }

    public function find($type, $args = ['fields' => [], 'conditions' => [], 'joins' => [], 'limit' => null, 'offset' => null, 'groupBy' => null,  'orderBy' => null])
    {
        if (isset($args['fields'])) {
            $this->setFields($args['fields']);
        } else {
            $this->setFields('*');
        }
        if (isset($args['limit'])) {
            $this->limit = ($args['limit']);
        }
        if (isset($args['offset'])) {
            $this->offset = ($args['offset']);
        }
        if (isset($args['groupBy'])) {
            $this->groupBy = ($args['groupBy']);
        }
        if (isset($args['orderBy'])) {
            $this->sort = ($args['orderBy']);
        }
        if (isset($args['joins'])) {
            $this->joins = ($args['joins']);
        }
        if (isset($args['conditions'])) {
            $this->params = $this->conditions($args['conditions']);
        }

        $this->statement = "";
        if (strtolower($type) == 'EXPLAIN') {
            $this->statement =  $type . PHP_EOL . $this->statement;
        } elseif (strtolower($type) == 'first') {
            $this->limit =  1;
            $this->sort = $this->name . '.id ASC';
        } elseif (strtolower($type) == 'last') {
            $this->limit =  1;
            $this->sort = $this->name . '.id DESC';
        }


        $this->statement .= "SELECT " .  implode(' , ', $this->fields) . " ";
        $this->statement .= "FROM $this->useTable as $this->name ";
        $this->statement .= "  " . $this->joins() . ' ';
        $this->statement .= "WHERE  $this->params ";
        // $this->statement .= "ORDER BY  $this->sort ";

        $this->statement .= (!empty($this->sort) ? "ORDER BY  $this->sort " : '');
        $this->statement .= (!empty($this->groupBy) ? "GROUP BY  $this->groupBy " : '');
        $this->statement .= (!empty($this->limit) ? "LIMIT  $this->limit" . empty($this->offset) ? '' : "/$this->offset "  : " ");



        $result = mysqli_query($this->connect, $this->statement);
        $mresult = [];
        if ($result)
            foreach (mysqli_fetch_all($result) as $count => $record) {
                foreach ($record as $key => $val) {
                    $details = mysqli_fetch_field_direct($result, $key);
                    if (!in_array(strtolower($type), array('first', 'last')))
                        $mresult[$count][$details->table][$details->name] = $val;
                    else    $mresult[$details->table][$details->name] = $val;
                }
            }
        else return $this->connect->error . PHP_EOL . $this->statement;
        //$this->mresult = $mresult;
        return $mresult;
    }

    public function joins()
    {
        $jointTables = '';
        $joins = (!empty(func_get_args()) ?  func_get_args() : $this->joins);
        if (!empty($joins)) {
            foreach ($joins as $jointTable) {
                $jointTables .= strtoupper($jointTable['type']) . " JOIN " . $jointTable['table'] . ' as ' . $jointTable['alias'];
                $jointTables .= ' ON ' . $this->conditions($jointTable['conditions']);
            }
        }
        return $jointTables;
    }

    public function setFields($args)
    {
        if (!is_array($args))
            $this->fields[] = $args;
        else {
            foreach ($args as $field) {
                if (is_array($field)) {
                    $this->setFields($field);
                } else {
                    $this->fields[] = $field;
                }
            }
        }
    }
    public function opWhere($field, $operator, $value)
    {
        $this->params[$field . ' ' . $operator]  = $value;
        return $this;
    }
    public function where($field, $value)
    {
        $this->params[$field]  = $value;
        return $this;
    }

    public function select()
    {

        $sql = (empty(func_get_args())) ? '*' : implode(',', func_get_args());

        $this->statement = "SELECT $sql FROM $this->useTable as $this->name ";
        return $this;
    }

    public function get()
    {
        $mresult = null;
        if (!empty($this->params)) {
            if (is_array($this->params))
                $this->statement .= "WHERE " . $this->conditions($this->params);
            else $this->statement = $this->params;
        }
        if (!empty($this->sort))
            $this->statement .= ' ORDER BY ' . $this->sort;
        if (!empty($this->limit))
            $this->statement .= ' LIMIT ' . $this->limit;

        $result = mysqli_query($this->connect, $this->statement);
        if (mysqli_num_rows($result) > 0) {

            while ($r = mysqli_fetch_assoc($result)) {
                // if (!in_array($this->_querytype, array('last', 'first')))
                //     $this->result[$r[key($r)]] = $r;
                // else {
                $mresult[] = $r;
                //}
            }
        }
        return $mresult;
    }

    public function first()
    {

        $this->params;
        $this->sort = "id ASC ";
        $this->limit  = 1;

        return $this->get();
    }
    public function last()
    {
        $this->sort = "id DESC ";
        $this->limit  = 1;

        return $this->get();
    }

    function conditions($cond, $defaultOperator = 'AND')
    {
        $conditions = null;

        foreach ($cond as $fields => $value) {
            if (is_numeric($fields)) {
                $conditions .= " " . $value;
            } elseif (!is_array($value)) {
                //$this->Log($fields);
                $addOp = false;

                $condOp = array_merge($this->ComparisonOperators, $this->LogicalOperators);
                foreach ($condOp as $operators) {
                    if (strpos($fields, $operators) !== false) {
                        $addOp = true;
                    }
                }

                if (!$addOp)
                    $conditions .= $fields . ' = ' . "'$value'";
                else $conditions .= $fields . " '$value'";
            } else {
                foreach ($this->LogicalOperators as $operators) {
                    if (strpos($fields, $operators) !== false) {
                        if ($operators == 'IN') {
                            $val = implode("',", $value);
                            $conditions .= "$fields ('$val') ";
                        } elseif ($operators == "BETWEEN") {
                            $val = implode("' AND '", $value);
                            $conditions .= "$fields '$val' ";
                        } elseif (in_array($operators, array("AND", "OR"))) {
                            $conditions = $this->conditions($value, $operators);
                        }
                    }
                    //else return $this->_conditions($value);
                }
            }
            $conditions .= " $defaultOperator ";
        }
        $conditions = rtrim($conditions, " $defaultOperator ");
        return $conditions;
    }
}
