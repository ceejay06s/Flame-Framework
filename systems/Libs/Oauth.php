<?php

class OAuth
{
    private $tables;
    private $db;
    private $dns;
    private $controller;
    public function __construct($controller = null)
    {
        include_once APP . 'config/database.php';

        $this->db = json_encode($default, true);
        $this->dns = "{$default['type']}:dbname={$default['database']};host={$default['host']}";

        $this->tables = array(
            'oauth_clients' => array(
                array('field' => 'client_id', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'client_secret', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'redirect_uri', 'dataType' => 'VARCHAR(2000)'),
                array('field' => 'grant_types', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'scope', 'dataType' => 'VARCHAR(4000)'),
                array('field' => 'user_id', 'dataType' => 'VARCHAR(80)'),
                array('PRIMARY KEY' => 'client_id'),
            ),
            'oauth_access_tokens' => array(
                array('field' => 'access_token', 'dataType' => 'VARCHAR(40)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'client_id', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'user_id', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'expires', 'dataType' => 'TIMESTAMP', 'ISNULL' => 'NOT NULL'),
                array('field' => 'scope', 'dataType' => 'VARCHAR(4000)'),
                array('PRIMARY KEY' => 'access_token'),
            ),
            'oauth_authorization_codes' => array(
                array('field' => 'authorization_code', 'dataType' => 'VARCHAR(40)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'client_id', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'user_id', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'redirect_uri', 'dataType' => 'VARCHAR(2000)'),
                array('field' => 'expires', 'dataType' => 'TIMESTAMP', 'ISNULL' => 'NOT NULL'),
                array('field' => 'scope', 'dataType' => 'VARCHAR(4000)'),
                array('field' => 'id_token', 'dataType' => 'VARCHAR(1000)'),
                array('PRIMARY KEY' => 'authorization_code')
            ),
            'oauth_refresh_tokens' => array(
                array('field' => 'refresh_token', 'dataType' => 'VARCHAR(40)', 'ISNULL' => 'NOT NULL'),
                array('field' =>  'client_id', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'user_id', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'expires', 'dataType' => 'TIMESTAMP', 'ISNULL' => 'NOT NULL'),
                array('field' => 'scope', 'dataType' => 'VARCHAR(4000)'),
                array('PRIMARY KEY' => 'refresh_token')
            ),
            'oauth_users' => array(
                array('field' => 'username', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'password', 'dataType' => 'VARCHAR(80'),
                array('field' => 'first_name', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'last_name', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'email', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'email_verified', 'dataType' => 'BOOLEAN'),
                array('field' => 'scope', 'dataType' => 'VARCHAR(4000)'),
                array('PRIMARY KEY' => 'username')
            ),
            'oauth_scopes' => array(
                array('field' => 'scope', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'is_default', 'dataType' => 'BOOLEAN'),
                array('PRIMARY KEY' => 'scope')
            ),
            'oauth_jwt' => array(
                array('field' => 'client_id', 'dataType' => 'VARCHAR(80)', 'ISNULL' => 'NOT NULL'),
                array('field' => 'subject', 'dataType' => 'VARCHAR(80)'),
                array('field' => 'public_key', 'dataType' => 'VARCHAR(2000)', 'ISNULL' => 'NOT NULL')
            ),
        );
        $this->controller = $controller;
    }
    public function DbCheckCreate()
    {
        echo "<pre>";
        foreach ($this->tables as $tablename => $table) {
            $sql = "CREATE TABLE  IF NOT EXIST $tablename";
            $field = [];
            foreach ($table as $record) {
                //var_dump($record['field']);
                $field[] = (isset($record['PRIMARY KEY']) ? "PRIMARY KEY ({$record['PRIMARY KEY']})" : $record['field'] . ' ' . $record['dataType'] . ' ' . (isset($record['ISNULL']) ? $record['ISNULL'] : ""));
            }
            $sql .= "(" . implode(',', $field) . ")";
            print_r($sql);
            //$this->controller->loadModel();
            /// echo $this->controller->Model->query('');
        }
    }
}
