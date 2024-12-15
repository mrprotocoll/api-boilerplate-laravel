<?php
$application = env('FRONTEND_URL', 'http://localhost');

return [
    // Pagination number
    'per_page' => 10,
    'user_dashboard' => $application.'/dashboard',
    'reset_password' => $application.'/reset_password?t=',
    'email_verification_link' => '/auth/login'
];

