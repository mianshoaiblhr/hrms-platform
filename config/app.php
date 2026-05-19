<?php
// ============================================================
// config/app.php - Application Configuration
// ============================================================

return [
    'name'         => getenv('APP_NAME') ?: 'HRMS Platform',
    'env'          => getenv('APP_ENV') ?: 'production',
    'url'          => getenv('APP_URL') ?: 'http://localhost',
    'key'          => getenv('APP_KEY') ?: '',
    'debug'        => getenv('APP_DEBUG') === 'true',
    'timezone'     => getenv('APP_TIMEZONE') ?: 'Asia/Karachi',
    'locale'       => getenv('APP_LOCALE') ?: 'en',
    'version'      => '1.0.0',

    'security' => [
        'session_lifetime'    => (int)(getenv('SESSION_LIFETIME') ?: 120),
        'max_login_attempts'  => (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5),
        'lockout_duration'    => (int)(getenv('LOCKOUT_DURATION') ?: 900),
        'password_expire_days'=> (int)(getenv('PASSWORD_EXPIRE_DAYS') ?: 90),
        'bcrypt_rounds'       => (int)(getenv('BCRYPT_ROUNDS') ?: 12),
        'csrf_lifetime'       => (int)(getenv('CSRF_TOKEN_LIFETIME') ?: 3600),
        'session_secure'      => getenv('SESSION_SECURE') === 'true',
    ],

    'upload' => [
        'path'       => getenv('STORAGE_PATH') ?: dirname(__DIR__) . '/storage/uploads',
        'max_size'   => (int)(getenv('MAX_UPLOAD_SIZE') ?: 10485760),
        'extensions' => explode(',', getenv('ALLOWED_EXTENSIONS') ?: 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png'),
    ],

    'log' => [
        'level'     => getenv('LOG_LEVEL') ?: 'warning',
        'path'      => getenv('LOG_PATH') ?: dirname(__DIR__) . '/storage/logs',
        'max_files' => (int)(getenv('LOG_MAX_FILES') ?: 30),
    ],
];
