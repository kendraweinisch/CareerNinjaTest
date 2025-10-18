<?php
// process_form.php - Handle contact form submissions

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
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$current_role = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
$service = filter_input(INPUT_POST, 'service', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Validate required fields
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (empty($current_role)) {
    $errors[] = "Current role is required";
}

if (empty($service)) {
    $errors[] = "Service selection is required";
}

// If there are errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Email configuration
$to = "kendraweinisch@gmail.com";
$subject = "New Executive Strategy Session Request - CareerNinja";

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
        .header { background: #1a5276; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #1a5276; }
        .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>New Executive Strategy Session Request</h1>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Name:</span> $name
            </div>
            <div class='field'>
                <span class='label'>Email:</span> <a href='mailto:$email'>$email</a>
            </div>
            <div class='field'>
                <span class='label'>Phone:</span> " . ($phone ? $phone : 'Not provided') . "
            </div>
            <div class='field'>
                <span class='label'>Current Role:</span> $current_role
            </div>
            <div class='field'>
                <span class='label'>Service Interested In:</span> $service
            </div>
            <div class='field'>
                <span class='label'>Message:</span><br>
                " . ($message ? nl2br(htmlspecialchars($message)) : 'No additional message provided') . "
            </div>
            <div class='field'>
                <span class='label'>Submission Time:</span> " . date('F j, Y, g:i a') . "
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the CareerNinja contact form.</p>
        </div>
    </div>
</body>
</html>
";

// Send email
$mail_sent = mail($to, $subject, $email_body, $headers);

// Log submission (optional)
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'service' => $service,
    'success' => $mail_sent
];

file_put_contents('form_submissions.log', json_encode($log_data) . PHP_EOL, FILE_APPEND | LOCK_EX);

// Return response
if ($mail_sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! Your request has been submitted successfully. We will contact you within 24 hours to confirm your session details.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, there was an error submitting your request. Please try again or contact us directly at kendraweinisch@gmail.com'
    ]);
}
?>