<?php
// process_book.php - Handle book notification form submissions

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data and sanitize
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$current_role = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
$interests = filter_input(INPUT_POST, 'interests', FILTER_SANITIZE_STRING);

// Validate required fields
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

// If there are errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Email configuration
$to = "kendraweinisch@gmail.com";
$subject = "New Book Notification Request - The Encore Executive";

// Email headers
$headers = "From: CareerNinja <noreply@yourcareerninja.com>" . "\r\n";
$headers .= "Reply-To: $email" . "\r\n";
$headers .= "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";

// Email body
$email_body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #e67e22; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #e67e22; }
        .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>New Book Notification Request</h1>
            <p>The Encore Executive - Book Updates</p>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Name:</span> $name
            </div>
            <div class='field'>
                <span class='label'>Email:</span> <a href='mailto:$email'>$email</a>
            </div>
            <div class='field'>
                <span class='label'>Current Role:</span> " . ($current_role ? $current_role : 'Not provided') . "
            </div>
            <div class='field'>
                <span class='label'>Areas of Interest:</span><br>
                " . ($interests ? nl2br(htmlspecialchars($interests)) : 'No specific interests mentioned') . "
            </div>
            <div class='field'>
                <span class='label'>Submission Time:</span> " . date('F j, Y, g:i a') . "
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the CareerNinja book notification form.</p>
        </div>
    </div>
</body>
</html>
";

// Send email
$mail_sent = mail($to, $subject, $email_body, $headers);

// Add to CSV file for email list (optional)
$csv_data = [
    date('Y-m-d H:i:s'),
    $name,
    $email,
    $current_role ?: 'Not provided',
    $interests ?: 'Not specified'
];

$csv_file = 'book_notifications.csv';
if (!file_exists($csv_file)) {
    // Create CSV with headers if it doesn't exist
    $header = ['Timestamp', 'Name', 'Email', 'Current Role', 'Interests'];
    file_put_contents($csv_file, implode(',', $header) . PHP_EOL);
}

file_put_contents($csv_file, implode(',', array_map(function($field) {
    return '"' . str_replace('"', '""', $field) . '"';
}, $csv_data)) . PHP_EOL, FILE_APPEND | LOCK_EX);

// Log submission (optional)
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'success' => $mail_sent
];

file_put_contents('book_submissions.log', json_encode($log_data) . PHP_EOL, FILE_APPEND | LOCK_EX);

// Return response
if ($mail_sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! You\'ve been added to our book notification list. We\'ll keep you updated on "The Encore Executive" publication timeline.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, there was an error processing your request. Please try again or contact us directly at kendraweinisch@gmail.com'
    ]);
}
?>