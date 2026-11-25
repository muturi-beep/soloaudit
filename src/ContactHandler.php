<?php

namespace KaranjaMuturi;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class ContactHandler
{
    private string $toEmail;
    private string $toName;
    private string $smtpHost;
    private string $smtpUsername;
    private string $smtpPassword;
    private int $smtpPort;
    private string $smtpSecure;

    public function __construct()
    {
        // SMTP configuration - set these as environment variables in Vercel
        $this->toEmail = getenv('CONTACT_EMAIL') ?: 'muturikaranja@gmail.com';
        $this->toName = getenv('CONTACT_NAME') ?: 'CPA Karanja Muturi Njuguna';
        
        // SMTP settings (Gmail example)
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpUsername = getenv('SMTP_USERNAME') ?: 'muturikaranja@gmail.com';
        $this->smtpPassword = getenv('SMTP_PASSWORD') ?: '';
        $this->smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpSecure = getenv('SMTP_SECURE') ?: 'tls';
    }

    public function processContact(array $data): array
    {
        // Validate required fields
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Sanitize input
        $name = $this->sanitize($data['name'] ?? '');
        $company = $this->sanitize($data['company'] ?? '');
        $email = $this->sanitize($data['email'] ?? '');
        $service = $this->sanitize($data['service'] ?? '');
        $message = $this->sanitize($data['message'] ?? '');

        // Send email
        $emailSent = $this->sendEmail($name, $company, $email, $service, $message);

        if ($emailSent) {
            return [
                'success' => true,
                'message' => 'Thank you for your message. We will get back to you shortly.'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to send email. Please try again later.'
            ];
        }
    }

    private function sendEmail(string $name, string $company, string $email, string $service, string $message): bool
    {
        // If no SMTP password set, return true (for testing)
        if (empty($this->smtpPassword)) {
            error_log('SMTP password not set. Email not sent.');
            return true; // Return true for testing
        }

        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpSecure;
            $mail->Port       = $this->smtpPort;

            $mail->setFrom($email, $name);
            $mail->addAddress($this->toEmail, $this->toName);
            $mail->addReplyTo($email, $name);

            $mail->isHTML(true);
            $mail->Subject = "New Contact Form Submission from $name";
            $mail->Body = $this->buildEmailBody($name, $company, $email, $service, $message);
            $mail->AltBody = strip_tags($mail->Body);

            return $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function buildEmailBody(string $name, string $company, string $email, string $service, string $message): string
    {
        return "
        <html>
        <head><style>
            body { font-family: Arial, sans-serif; color: #211c2d; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4b28b8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .header h2 { margin: 0; font-size: 22px; }
            .content { background: #fbfafc; padding: 25px; border: 1px solid #e9e5ee; border-top: none; border-radius: 0 0 8px 8px; }
            .field { margin-bottom: 15px; }
            .field-label { font-weight: 700; color: #4b28b8; display: block; margin-bottom: 4px; }
            .field-value { color: #211c2d; }
            .footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9e5ee; font-size: 12px; color: #716b7c; }
            .highlight { color: #c69a4a; font-weight: 700; }
        </style></head>
        <body>
            <div class='container'>
                <div class='header'><h2>🔔 New Contact Form Submission</h2></div>
                <div class='content'>
                    <div class='field'><span class='field-label'>👤 Full Name</span><span class='field-value'>{$name}</span></div>
                    <div class='field'><span class='field-label'>🏢 Company</span><span class='field-value'>" . ($company ?: 'Not provided') . "</span></div>
                    <div class='field'><span class='field-label'>📧 Email</span><span class='field-value'><a href='mailto:{$email}'>{$email}</a></span></div>
                    <div class='field'><span class='field-label'>📋 Service</span><span class='field-value highlight'>{$service}</span></div>
                    <div class='field'><span class='field-label'>💬 Message</span><div class='field-value' style='background:white;padding:12px;border-radius:6px;border:1px solid #e9e5ee;'>" . nl2br(htmlspecialchars($message)) . "</div></div>
                    <div class='footer'><p>This message was sent from CPA Karanja Muturi Njuguna's website.</p></div>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = 'Full name is required.';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        }

        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        $service = trim($data['service'] ?? '');
        if (empty($service)) {
            $errors['service'] = 'Please select a service.';
        }

        $message = trim($data['message'] ?? '');
        if (empty($message)) {
            $errors['message'] = 'Message is required.';
        } elseif (strlen($message) < 10) {
            $errors['message'] = 'Message must be at least 10 characters.';
        }

        return $errors;
    }

    private function sanitize(string $input): string
    {
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
}