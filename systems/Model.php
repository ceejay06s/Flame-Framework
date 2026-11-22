<?php

namespace Flame;

use PDO;
use InvalidArgumentException;

class Model
{
    public $con;
    public $scheme = 'default';
    public $config = [];
    public $query;
    public $table;
    public $controller;
    protected $record = [];

    public function __construct($controller = null)
    {
        $this->controller = $controller;
        $configurations = require APP . 'config' . DS . 'database.php';

        if (!isset($configurations[$this->scheme]) || !is_array($configurations[$this->scheme])) {
            throw new InvalidArgumentException("Database configuration '{$this->scheme}' was not found.");
        }

        $this->config = $configurations[$this->scheme];
        $driver = strtolower((string) ($this->config['type'] ?? ''));
        if ($driver === 'mssql') {
            $driver = 'sqlsrv';
        }
        if (!in_array($driver, ['mysql', 'pgsql', 'sqlsrv'], true)) {
            throw new InvalidArgumentException("Unsupported database driver '{$driver}'.");
        }
        if (empty($this->config['database'])) {
            throw new InvalidArgumentException('DB_DATABASE must be configured.');
        }

        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? ($driver === 'pgsql' ? 5432 : ($driver === 'sqlsrv' ? 1433 : 3306)));
        $database = (string) $this->config['database'];

        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $host,
                    $port,
                    $database,
                    (string) ($this->config['charset'] ?? 'utf8mb4')
                );
                break;
            case 'pgsql':
                $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);
                break;
            default:
                $dsn = sprintf('sqlsrv:Server=%s,%d;Database=%s', $host, $port, $database);
        }

        $this->config['type'] = $driver;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if ($driver !== 'sqlsrv') {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
        }
        $this->con = new PDO(
            $dsn,
            (string) ($this->config['username'] ?? ''),
            (string) ($this->config['password'] ?? ''),
            $options
        );

        $className = (new \ReflectionClass($this))->getShortName();
        $this->table = $this->table ?: strtolower(Inflector::pluralize($className));
    }

    public function execute($query, array $parameters = [])
    {
        $statement = $this->con->prepare($query);
        $statement->execute($parameters);
        $this->query = $query;

        return $statement;
    }

    public function first($query, array $parameters = [])
    {
        $row = $this->execute($query, $parameters)->fetch();
        return $row === false ? null : $row;
    }

    public function all($query, array $parameters = [])
    {
        return $this->execute($query, $parameters)->fetchAll();
    }

    public function create(array $data = [])
    {
        $this->record = $data;
        return $this;
    }

    public function __set($name, $value)
    {
        $this->quoteIdentifier($name);
        $this->record[$name] = $value;
    }

    public function __get($name)
    {
        return $this->record[$name] ?? null;
    }

    public function insert(array $data)
    {
        if ($data === []) {
            throw new InvalidArgumentException('Cannot insert an empty record.');
        }

        $columns = [];
        $placeholders = [];
        $parameters = [];
        foreach ($data as $field => $value) {
            $columns[] = $this->quoteIdentifier($field);
            $placeholder = ':value_' . count($parameters);
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $value;
        }

        return $this->execute(
            'INSERT INTO ' . $this->quoteIdentifier($this->table)
                . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $parameters
        );
    }

    public function save(array $data = [])
    {
        $record = $data !== [] ? $data : $this->record;
        $saved = $this->insert($record)->rowCount() > 0;
        if ($saved) {
            $this->record = $record;
        }
        return $saved;
    }

    public function update(array $data, array $conditions)
    {
        if ($data === [] || $conditions === []) {
            throw new InvalidArgumentException('Updates require values and at least one condition.');
        }

        $assignments = [];
        $parameters = [];
        foreach ($data as $field => $value) {
            $column = $this->quoteIdentifier($field);
            $placeholder = ':value_' . count($parameters);
            $assignments[] = "{$column} = {$placeholder}";
            $parameters[$placeholder] = $value;
        }
        $where = $this->buildConditions($conditions, $parameters);

        return $this->execute(
            'UPDATE ' . $this->quoteIdentifier($this->table) . ' SET ' . implode(', ', $assignments) . ' WHERE ' . $where,
            $parameters
        );
    }

    public function delete(array $conditions)
    {
        if ($conditions === []) {
            throw new InvalidArgumentException('Delete requires at least one condition.');
        }
        $parameters = [];
        $where = $this->buildConditions($conditions, $parameters);

        return $this->execute(
            'DELETE FROM ' . $this->quoteIdentifier($this->table) . ' WHERE ' . $where,
            $parameters
        );
    }

    public function find($type = 'all', array $options = [])
    {
        if (!in_array($type, ['first', 'all'], true)) {
            throw new InvalidArgumentException("Unsupported find type '{$type}'.");
        }
        $unsupportedOptions = array_diff(array_keys($options), ['conditions']);
        if ($unsupportedOptions !== []) {
            throw new InvalidArgumentException('Unsupported find option: ' . reset($unsupportedOptions));
        }

        $table = $this->quoteIdentifier($this->table);
        $top = $type === 'first' && $this->config['type'] === 'sqlsrv' ? 'TOP (1) ' : '';
        $sql = "SELECT {$top}* FROM {$table}";
        $parameters = [];
        $conditions = $options['conditions'] ?? [];

        if (!is_array($conditions)) {
            throw new InvalidArgumentException('Find conditions must be an associative array.');
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . $this->buildConditions($conditions, $parameters);
        }
        if ($type === 'first' && $this->config['type'] !== 'sqlsrv') {
            $sql .= ' LIMIT 1';
        }

        $statement = $this->execute($sql, $parameters);
        if ($type === 'first') {
            $row = $statement->fetch();
            return $row === false ? null : [(new \ReflectionClass($this))->getShortName() => $row];
        }

        $modelName = (new \ReflectionClass($this))->getShortName();
        return array_map(static function (array $row) use ($modelName) {
            return [$modelName => $row];
        }, $statement->fetchAll());
    }

    private function buildConditions(array $conditions, array &$parameters)
    {
        $clauses = [];
        foreach ($conditions as $field => $value) {
            if (!is_string($field)) {
                throw new InvalidArgumentException('Condition fields must be strings.');
            }
            $fieldParts = explode('.', $field);
            if (count($fieldParts) === 2) {
                $expected = [$this->table, (new \ReflectionClass($this))->getShortName()];
                if (!in_array($fieldParts[0], $expected, true)) {
                    throw new InvalidArgumentException("Invalid condition qualifier '{$fieldParts[0]}'.");
                }
                $field = $fieldParts[1];
            } elseif (count($fieldParts) !== 1) {
                throw new InvalidArgumentException("Invalid condition field '{$field}'.");
            }

            $column = $this->quoteIdentifier($field);
            if ($value === null) {
                $clauses[] = "{$column} IS NULL";
                continue;
            }

            $parameter = ':condition_' . count($parameters);
            $clauses[] = "{$column} = {$parameter}";
            $parameters[$parameter] = $value;
        }

        return implode(' AND ', $clauses);
    }

    private function quoteIdentifier($identifier)
    {
        if (!is_string($identifier) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $identifier)) {
            throw new InvalidArgumentException('Invalid SQL identifier.');
        }

        switch ($this->config['type']) {
            case 'mysql':
                return '`' . $identifier . '`';
            case 'sqlsrv':
                return '[' . $identifier . ']';
            default:
                return '"' . $identifier . '"';
        }
    }
}
