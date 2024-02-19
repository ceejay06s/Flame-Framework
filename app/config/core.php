<?php

/* ini */

$config('ini__display_errors', false);
$config('ini__display_startup_errors', true);
/* Secuurity Hashing */
$config('securitySalt', 'E9C373e3cc9111AA305821413BEF4ab4a24e48ee');
$config('hash', 'MD5');

//session
$config('session_path', APP . 'tmp/cache/session');

/* Mail Service */

$config('smtp_service', "PhpMailer"); // native || sendmail || PhpMailer 
$config('smtp_server', "smtp-relay.brevo.com");
$config('smtp_port', 587);
$config('smtp_protocol', 'auto');
$config('smtp_username', 'christianbalais06@gmail.com');
$config('smtp_password', "xsmtpsib-4b37f58dc9e6341600e8214947c93eabeeb2378ab825198af6633c71e9a50b2b-7DzVxw9P5vy1fmKq");
$config('smtp_XMailer', "XMail-OMEPH-V1");
$config('ini__sendmail_path', SYSTEM . 'Tools/sendmail/sendmail -t -i');
