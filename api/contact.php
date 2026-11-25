<?php
/**
 * Vercel Serverless Function Entry Point
 * /api/contact endpoint
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Get the raw input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to form data if not JSON
if (!$input) {
    $input = $_POST;
}

// Validate input
$errors = [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$service = trim($input['service'] ?? '');
$message = trim($input['message'] ?? '');
$company = trim($input['company'] ?? '');

if (empty($name)) {
    $errors['name'] = 'Full name is required.';
} elseif (strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters.';
}

if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (empty($service)) {
    $errors['service'] = 'Please select a service.';
}

if (empty($message)) {
    $errors['message'] = 'Message is required.';
} elseif (strlen($message) < 10) {
    $errors['message'] = 'Message must be at least 10 characters.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Log the submission (for debugging)
$logEntry = date('Y-m-d H:i:s') . " - Name: $name, Email: $email, Service: $service, Message: $message\n";
file_put_contents(__DIR__ . '/../submissions.log', $logEntry, FILE_APPEND);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you for your message. We will get back to you shortly.'
]);