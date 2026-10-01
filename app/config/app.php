
<?php
// app/config/app.php

return [
    'name'        => 'DonTech PeopleSuite',
    'env'         => process_env('APP_ENV', 'production'),
    'url'         => process_env('APP_URL', 'http://localhost/HR/public'),
    'timezone'    => 'Africa/Dar_es_Salaam',
    'currency'    => 'TZS',
    'locale'      => 'en_TZ',
    'session_name'=> 'DONTECH_PEOPLESUITE_SESS',
    'upload_dir'  => __DIR__ . '/../../public/uploads',
    'private_upload_dir' => process_env('HR_PRIVATE_UPLOAD_DIR', __DIR__ . '/../../storage/uploads'),
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
];
