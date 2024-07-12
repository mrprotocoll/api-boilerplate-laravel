<?php
$application = env('FRONTEND_URL', 'http://localhost');

return [
    // Pagination number
    'per_page' => 10,
    'user_dashboard' => $application.'/dashboard',
    'email_verification_link' => '/auth/login'
];

