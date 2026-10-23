<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

// Logout is a POST so a random <img src="logout.php"> can't sign users out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

auth()->verifyCsrf($_POST['_csrf'] ?? null);
auth()->logout();
header('Location: login.php');
exit;