<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('APP')) {
    define('APP', dirname(__DIR__) . DS . 'app' . DS);
}
if (!defined('CONTROLLERS')) {
    define('CONTROLLERS', APP . 'controllers' . DS);
}
ob_start();

require_once __DIR__ . '/../systems/Libs/Jwt.php';
require_once __DIR__ . '/../systems/Libs/Scheduler.php';
require_once __DIR__ . '/../systems/Libs/Session.php';
require_once __DIR__ . '/../systems/Router.php';
require_once __DIR__ . '/../systems/Core.php';

$passed = 0;
$failed = 0;

$test = static function ($name, callable $callback) use (&$passed, &$failed) {
    try {
        $callback();
        $passed++;
        echo "PASS {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        fwrite(STDERR, "FAIL {$name}: {$exception->getMessage()}" . PHP_EOL);
    }
};

$same = static function ($expected, $actual, $message = 'Values did not match.') {
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
};

$throws = static function ($class, callable $callback) {
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($exception instanceof $class) {
            return;
        }
        throw new RuntimeException('Unexpected exception type: ' . get_class($exception));
    }
    throw new RuntimeException("Expected exception {$class} was not thrown.");
};

$test('Router matches explicit routes and enforces HTTP methods', static function () use ($same, $throws) {
    $router = new \Flame\Router();
    $router->addRoute('/user/login', 'UserController', 'login', [], ['GET', 'POST']);

    $match = $router->match('/user/login', 'POST');
    $same('UserController', $match['controller']);
    $same('login', $match['action']);

    $wrongMethod = $router->match('/user/login', 'DELETE');
    $same(true, $wrongMethod['method_not_allowed']);
    $same(['GET', 'POST'], $wrongMethod['allow']);
    $same(null, $router->match('/missing', 'GET'));

    $throws(InvalidArgumentException::class, static function () use ($router) {
        $router->addRoute('/user/login', 'UserController', 'login');
    });
});

$test('Router rejects traversal and encoded path separators', static function () use ($throws) {
    foreach (['/../systems/Model.php', '/%2fhome', '/home\\about', '//home'] as $path) {
        $throws(InvalidArgumentException::class, static function () use ($path) {
            \Flame\Router::normalizePath($path);
        });
    }
});

$test('Router rejects external and header-injection redirects', static function () use ($throws) {
    foreach (['https://example.test', '//example.test', "/safe\r\nLocation: https://example.test"] as $url) {
        $throws(InvalidArgumentException::class, static function () use ($url) {
            \Flame\Router::redirect($url);
        });
    }
});

$test('Core returns safe 404 and 405 responses for unregistered dispatch', static function () use ($same) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/framework-missing-route';
    http_response_code(200);
    ob_start();
    new \Flame\Core();
    $notFound = ob_get_clean();
    $same(404, http_response_code());
    $same('Not Found', $notFound);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/home/about';
    http_response_code(200);
    ob_start();
    new \Flame\Core();
    $methodNotAllowed = ob_get_clean();
    $same(405, http_response_code());
    $same('Method Not Allowed', $methodNotAllowed);
});

$test('JWT signs and verifies claims', static function () use ($same) {
    $secret = bin2hex(random_bytes(32));
    $token = \Flame\Jwt::encode(['sub' => 'user-1', 'iss' => 'flame-test', 'aud' => 'test-api'], $secret);
    $claims = \Flame\Jwt::decode($token, $secret, [
        'issuer' => 'flame-test',
        'audience' => 'test-api',
    ]);

    $same('user-1', $claims['sub']);
    if (!isset($claims['iat'], $claims['exp'])) {
        throw new RuntimeException('JWT time claims were not added.');
    }
});

$test('JWT rejects tampered signatures and expired claims', static function () use ($throws) {
    $secret = bin2hex(random_bytes(32));
    $token = \Flame\Jwt::encode(['sub' => 'user-1'], $secret);
    $segments = explode('.', $token);
    $segments[2] = str_repeat('A', strlen($segments[2]));
    $throws(InvalidArgumentException::class, static function () use ($segments, $secret) {
        \Flame\Jwt::decode(implode('.', $segments), $secret);
    });

    $expired = \Flame\Jwt::encode(['sub' => 'user-1', 'exp' => time() - 10], $secret);
    $throws(InvalidArgumentException::class, static function () use ($expired, $secret) {
        \Flame\Jwt::decode($expired, $secret);
    });
});

$test('Cron expressions match expected times and reject invalid fields', static function () use ($same, $throws) {
    $date = new DateTimeImmutable('2026-11-22 12:30:00');
    $same(true, \Flame\CronExpression::isDue('*/15 12 * * *', $date));
    $same(false, \Flame\CronExpression::isDue('*/20 12 * * *', $date));
    $throws(InvalidArgumentException::class, static function () {
        \Flame\CronExpression::validate('61 * * * *');
    });
});

$test('Scheduler runs a task only once in a minute', static function () use ($same) {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flame-test-' . bin2hex(random_bytes(8));
    $runs = 0;
    $time = new DateTimeImmutable('2026-11-22 12:30:00');

    try {
        $first = new \Flame\Scheduler($directory);
        $first->everyMinute('sample', static function () use (&$runs) {
            $runs++;
        });
        $first->run($time);

        $second = new \Flame\Scheduler($directory);
        $second->everyMinute('sample', static function () use (&$runs) {
            $runs++;
        });
        $second->run($time);
        $same(1, $runs);
    } finally {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.lock') ?: [] as $lockFile) {
            unlink($lockFile);
        }
        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
});

$test('Session uses strict mode and preserves falsey values', static function () use ($same) {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flame-session-' . bin2hex(random_bytes(8));
    if (!mkdir($directory, 0700)) {
        throw new RuntimeException('Could not create temporary session directory.');
    }

    putenv('SESSION_PATH=' . $directory);
    putenv('SESSION_COOKIE_SECURE=0');
    try {
        $session = new \Flame\Session();
        $same(PHP_SESSION_ACTIVE, session_status());
        $same('1', ini_get('session.use_strict_mode'));
        $same('1', ini_get('session.use_only_cookies'));
        $same('0', ini_get('session.use_trans_sid'));

        $session->set('zero', 0);
        $same(0, $session->get('zero'));
        $cookie = session_get_cookie_params();
        $same(true, $cookie['httponly']);
        $same('Lax', $cookie['samesite']);

        $sessionId = session_id();
        session_write_close();
        $sessionFile = $directory . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        if (is_file($sessionFile)) {
            unlink($sessionFile);
        }
        rmdir($directory);
    } finally {
        putenv('SESSION_PATH');
        putenv('SESSION_COOKIE_SECURE');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
});

echo sprintf('%d passed, %d failed.' . PHP_EOL, $passed, $failed);
$output = ob_get_clean();
echo $output;
exit($failed === 0 ? 0 : 1);
