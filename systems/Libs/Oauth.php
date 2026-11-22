<?php

class OAuth
{
    public function installSchema(PDO $connection)
    {
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateType = $driver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME';
        $definitions = [
            'oauth_clients' => 'client_id VARCHAR(80) NOT NULL PRIMARY KEY, client_secret VARCHAR(255), redirect_uri VARCHAR(2000), grant_types VARCHAR(80), scope VARCHAR(4000), user_id VARCHAR(80)',
            'oauth_access_tokens' => "access_token VARCHAR(80) NOT NULL PRIMARY KEY, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(80), expires {$dateType} NOT NULL, scope VARCHAR(4000)",
            'oauth_authorization_codes' => "authorization_code VARCHAR(80) NOT NULL PRIMARY KEY, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(80), redirect_uri VARCHAR(2000), expires {$dateType} NOT NULL, scope VARCHAR(4000), id_token VARCHAR(1000)",
            'oauth_refresh_tokens' => "refresh_token VARCHAR(80) NOT NULL PRIMARY KEY, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(80), expires {$dateType} NOT NULL, scope VARCHAR(4000)",
            'oauth_users' => 'username VARCHAR(80) NOT NULL PRIMARY KEY, password VARCHAR(255), first_name VARCHAR(80), last_name VARCHAR(80), email VARCHAR(255), email_verified SMALLINT, scope VARCHAR(4000)',
            'oauth_scopes' => 'scope VARCHAR(80) NOT NULL PRIMARY KEY, is_default SMALLINT',
            'oauth_jwt' => 'client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000) NOT NULL',
        ];

        if (!in_array($driver, ['mysql', 'pgsql', 'sqlsrv'], true)) {
            throw new InvalidArgumentException("OAuth schema installation is not supported for PDO driver '{$driver}'.");
        }

        foreach ($definitions as $table => $columns) {
            if ($driver === 'sqlsrv') {
                $sql = "IF OBJECT_ID(N'{$table}', N'U') IS NULL CREATE TABLE {$table} ({$columns})";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS {$table} ({$columns})";
            }
            $connection->exec($sql);
        }
    }

    public function createServer(PDO $connection, array $options = [])
    {
        if (!class_exists('OAuth2\\Server') || !class_exists('OAuth2\\Storage\\Pdo')) {
            throw new RuntimeException('Install the Composer dependencies before using OAuth.');
        }

        $storage = new OAuth2\Storage\Pdo($connection);
        $defaults = [
            'allow_implicit' => false,
            'access_lifetime' => 3600,
            'refresh_token_lifetime' => 1209600,
        ];
        $server = new OAuth2\Server($storage, array_replace($defaults, $options));
        $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
        $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
        $server->addGrantType(new OAuth2\GrantType\RefreshToken($storage));

        return $server;
    }

    public function handleTokenRequest($server, $request = null)
    {
        if (!is_object($server) || !method_exists($server, 'handleTokenRequest')) {
            throw new InvalidArgumentException('A configured OAuth server is required.');
        }
        if ($request === null) {
            $request = OAuth2\Request::createFromGlobals();
        }

        return $server->handleTokenRequest($request);
    }

    public function handleAuthorizationRequest($server, $approved, $userId, $request = null)
    {
        if (!is_object($server) || !method_exists($server, 'handleAuthorizeRequest')) {
            throw new InvalidArgumentException('A configured OAuth server is required.');
        }
        if ($request === null) {
            $request = OAuth2\Request::createFromGlobals();
        }

        $response = new OAuth2\Response();
        if (!$server->validateAuthorizeRequest($request, $response)) {
            return $response;
        }

        return $server->handleAuthorizeRequest($request, $response, (bool) $approved, $userId);
    }

    public function verifyResourceRequest($server, $request = null)
    {
        if (!is_object($server) || !method_exists($server, 'verifyResourceRequest')) {
            throw new InvalidArgumentException('A configured OAuth server is required.');
        }
        if ($request === null) {
            $request = OAuth2\Request::createFromGlobals();
        }

        $response = new OAuth2\Response();
        return [
            'valid' => $server->verifyResourceRequest($request, $response),
            'response' => $response,
        ];
    }
}
