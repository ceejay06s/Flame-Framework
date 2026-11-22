# Framework integrations

## Database

Use `.env.example` as a reference and provide its values to the PHP process through your process manager or deployment platform. The framework does not parse `.env` files. Generate independent, random values for `APP_KEY` and `JWT_SECRET` and store them in a secret manager; do not use the empty example values in production. Set `DB_CONNECTION` to `mysql`, `pgsql`, `sqlsrv`, or `mssql` (`mssql` is an alias for `sqlsrv`) and provide the matching PDO extension and connection values. The configured database must already exist; Flame does not create databases automatically.

Models expose parameterized `execute()`, `first()`, `all()`, `insert()`, `update()`, and `delete()` methods, plus `create()`/`save()` for inserts. Update and delete operations require explicit conditions. For example:

```php
$user = $this->User->first(
    'SELECT id, username FROM users WHERE username = :username',
    [':username' => $username]
);
```

`find('first', ['conditions' => ['User.username' => $username]])` and `find('all', ...)` are also available for simple equality conditions. Use prepared statements for values; identifiers passed to `find()` are validated and quoted for the active database.

## OAuth 2.0 and JWT

OAuth uses the existing `bshaffer/oauth2-server-php` dependency and requires OAuth tables in the configured database. Install them once from a deployment/migration step, not on every web request:

```php
useLibrary('Oauth', 'libs', SYSTEM);

$this->loadModel('User');
$oauth = new OAuth();
$oauth->installSchema($this->User->con);
$server = $oauth->createServer($this->User->con);
```

Expose token and authorization endpoints in application controllers. `handleTokenRequest($server)` creates a request from PHP globals; `handleAuthorizationRequest($server, $approved, $userId)` supports the authorization-code decision after the application authenticates and obtains consent from the user; `verifyResourceRequest($server)` returns a `valid` flag and OAuth response for a protected endpoint. Send that response when validation fails. Protect the server and its client credentials with HTTPS and register exact redirect URIs.

For a token endpoint, send the response returned by `handleTokenRequest()` with its `send()` method. OAuth schema installation should run only during deployment; do not call `installSchema()` on each request.

The JWT helper supports HS256 and requires a separately managed `JWT_SECRET` with adequate entropy. Authentication password hashing also requires a private `APP_KEY`. It validates signatures, expiration, not-before, and optional issuer/audience claims:

```php
useLibrary('Jwt', 'libs', SYSTEM);

$token = \Flame\Jwt::encode(['sub' => (string) $userId], getenv('JWT_SECRET'), 3600);
$claims = \Flame\Jwt::decode($token, getenv('JWT_SECRET'), [
    'issuer' => 'https://example.test',
    'audience' => 'my-api',
]);
```

Do not use JWTs as a replacement for authorization checks. Keep signing keys outside source control and rotate them through your deployment process.

## Scheduled tasks

Register callbacks in `app/config/schedule.php`:

```php
return static function (\Flame\Scheduler $schedule) {
    $schedule->dailyAt('cleanup', '02:30', static function () {
        // Run application maintenance here.
    });
};
```

The scheduler supports numeric five-field cron expressions (including lists, ranges, and steps) and convenience methods `everyMinute()`, `hourly()`, and `dailyAt()`. It uses per-task file locks and records the last successful run per minute. Configure the operating system to invoke the runner once per minute:

```text
php /path/to/Flame-Framework/console/schedule.php
```

On Windows, create the equivalent recurring task in Task Scheduler. Tasks run only when the PHP CLI process is invoked; the framework does not run a persistent scheduler daemon.

## Email transports

Set `MAIL_TRANSPORT` to `native`, `sendmail`, `phpmailer`, or `curl`. The cURL transport sends JSON by POST to `MAIL_CURL_ENDPOINT`, optionally with `Authorization: Bearer <MAIL_CURL_API_KEY>`. Its JSON fields are `to`, `from`, `from_name`, `reply_to`, `subject`, `body`, and `html`. Configure the endpoint to accept this contract. Non-2xx responses and transport errors throw exceptions.

## React, Vue, Angular, and Firebase starters

Flame endpoints can return JSON with `$this->json($data, $status)`. The UI frameworks are separate clients and may be deployed independently; configure the API URL and CORS policy for your deployment. Do not put database credentials, OAuth client secrets, or JWT signing keys in browser code.

### React

React and React DOM are already listed in the root `package.json`.

```jsx
import { useEffect, useState } from 'react';

export function Status() {
  const [status, setStatus] = useState(null);
  useEffect(() => {
    fetch('/api/status')
      .then((response) => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then(setStatus)
      .catch((error) => console.error('API request failed', error));
  }, []);

  return <pre>{JSON.stringify(status, null, 2)}</pre>;
}
```

### Vue

Vue is already listed in the root `package.json`.

```vue
<script setup>
import { onMounted, ref } from 'vue';

const status = ref(null);
onMounted(async () => {
  const response = await fetch('/api/status');
  if (!response.ok) throw new Error(`HTTP ${response.status}`);
  status.value = await response.json();
});
</script>

<template><pre>{{ JSON.stringify(status, null, 2) }}</pre></template>
```

### Angular

Create an Angular client with the Angular CLI, then provide `HttpClient` and consume the same JSON endpoint:

```typescript
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({ providedIn: 'root' })
export class FlameApi {
  private readonly http = inject(HttpClient);
  status() {
    return this.http.get<{ status: string }>('/api/status');
  }
}
```

### Firebase

Install the Firebase client SDK in the frontend project with `npm install firebase`. Initialize it with the public Firebase web-app configuration and Firebase security rules. Firebase web configuration is not a server secret; restrict access with Firebase Authentication and security rules:

```javascript
import { initializeApp } from 'firebase/app';
import { getAuth, signInWithEmailAndPassword } from 'firebase/auth';

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const credential = await signInWithEmailAndPassword(auth, email, password);
const idToken = await credential.user.getIdToken();
const response = await fetch('/api/private', {
  headers: { Authorization: `Bearer ${idToken}` },
});
```

Flame does not implicitly validate Firebase tokens. Implement and configure server-side token verification before treating that token as authenticated.
