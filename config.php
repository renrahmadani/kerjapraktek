<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host = '127.0.0.1';
$db   = 'db_wahanaindotrada';
$user = 'root';
$pass = ''; // Default Laragon root no password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect to MySQL server first (without database to allow database creation)
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if database exists, create if not
    $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    if (!$stmt->fetchColumn()) {
        $pdo->exec("CREATE DATABASE `$db` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
    }
    
    // Connect explicitly to the database
    $pdo->exec("USE `$db`");
    
    // Auto-alter safe table schema migration for lifecycle timestamps
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `proses_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`");
    } catch (\PDOException $e) { /* Column likely exists */ }
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `selesai_at` TIMESTAMP NULL DEFAULT NULL AFTER `proses_at`");
    } catch (\PDOException $e) { /* Column likely exists */ }
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `batal_at` TIMESTAMP NULL DEFAULT NULL AFTER `selesai_at`");
    } catch (\PDOException $e) { /* Column likely exists */ }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Global Unread Notifications Count
$unread_notifs = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $r = $_SESSION['role'] ?? 'customer';
    try {
        if ($r === 'admin') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0");
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$uid]);
        }
        $unread_notifs = $stmt->fetchColumn();
    } catch (\PDOException $e) { /* ignore during init */ }
}

// Load Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper: Send Email Notification via Gmail SMTP
function send_email_notification($to_email, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'vandptr@gmail.com'; // Admin Gmail
        $mail->Password   = 'pehtzfalttolosbk';  // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('vandptr@gmail.com', 'Wahana Indo Trada Service');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
