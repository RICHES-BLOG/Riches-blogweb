<?php
// 1. Verify Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

// 2. reCAPTCHA Gatekeeper
$secretKey = "YOUR_SECRET_KEY";
$response = $_POST['g-recaptcha-response'] ?? '';
$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secretKey.'&response='.$response);
$responseData = json_decode($verifyResponse);

if (!$responseData || !$responseData->success) {
    die('Security verification failed. Invalid CAPTCHA.');
}

// 3. Input Sanitization & Extraction
// strip_tags removes potential HTML/JavaScript payloads
$name = strip_tags(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message = strip_tags(trim($_POST['message'] ?? ''));

// 4. Strict Validation Routing
// Prevent CRLF Header Injection by ensuring no line breaks exist in the email string
if (preg_match('/[\r\n]/', $email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: spam-error.php');
    exit;
}

// 5. Spam Keyword Filter
$spam_keywords = array('viagra', 'casino', 'porn', 'sex');
$spam_found = false;

foreach ($spam_keywords as $keyword) {
    // \b ensures exact word boundaries are matched (e.g., 'sex' triggers, but 'Middlesex' does not)
    if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $message)) {
        $spam_found = true;
        break;
    }
}

if ($spam_found) {
    header('Location: spam-error.php');
    exit;
}

// 6. Secure Email Dispatch
$to = 'richesbosses@gmail.com';
$subject = 'Contact Form Submission';
$body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

// Hardcode the "From" address to a domain you control to prevent DMARC/SPF delivery failures
$headers = 'From: noreply@yourdomain.com' . "\r\n" .
           'Reply-To: ' . $email . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $body, $headers)) {
    echo 'Thank you for contacting us! We will respond to your message shortly.';
} else {
    echo 'System failure: Unable to dispatch the message at this time.';
}
?>
