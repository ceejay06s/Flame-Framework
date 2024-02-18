<?php

namespace Flame;

use PDO;

class Model
{
    var $con;
    var $scheme = 'default';
    var $config;
    var $query;
    var $table;
    function __construct($controller = null)
    {
        include APP . 'config/database.php';
        $this->config = get_defined_vars()[$this->scheme];
        try {
            $this->con = new PDO("{$this->config['type']}:host={$this->config['host']};port={$this->config['port']};charset this->config['charset']}", $this->config['username'], $this->config['password']);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->query = "CREATE DATABASE IF NOT EXISTS {$this->config['database']};";
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        $this->query = "use {$this->config['database']};";
        $this->con->exec($this->query);
    }
    function execute($query)
    {
        $res = $this->con->prepare($query);

        $res->execute();
        $res->setFetchMode(PDO::FETCH_ASSOC);
        print_r(($res->fetch()));
    }
}
