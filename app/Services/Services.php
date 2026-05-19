<?php
namespace App\Services;

use App\Core\Database;

/**
 * Email Service
 */
class EmailService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'host'     => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'port'     => (int)env('MAIL_PORT', 587),
            'username' => env('MAIL_USERNAME', ''),
            'password' => env('MAIL_PASSWORD', ''),
            'from'     => env('MAIL_FROM_ADDRESS', 'noreply@hrms.com'),
            'name'     => env('MAIL_FROM_NAME', 'HRMS System'),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        ];
    }

    public function send(string $to, string $subject, string $body, string $toName = ''): bool
    {
        // Uses PHP mail() as fallback; production should use PHPMailer/SwiftMailer
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->config['name']} <{$this->config['from']}>\r\n";
        $headers .= "Reply-To: {$this->config['from']}\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION;

        try {
            return mail($to, $subject, $this->wrapTemplate($subject, $body), $headers);
        } catch (\Throwable $e) {
            error_log("EmailService error: " . $e->getMessage());
            return false;
        }
    }

    private function wrapTemplate(string $subject, string $body): string
    {
        $company = env('APP_NAME', 'HRMS');
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
  .header{background:#2563eb;color:#fff;padding:25px 30px;font-size:20px;font-weight:bold}
  .body{padding:30px;color:#333;line-height:1.6}
  .footer{background:#f9f9f9;padding:15px 30px;font-size:12px;color:#999;text-align:center}
</style>
</head>
<body>
  <div class="container">
    <div class="header">$company</div>
    <div class="body">$body</div>
    <div class="footer">&copy; {$company}. This is an automated email, please do not reply.</div>
  </div>
</body></html>
HTML;
    }

    public function sendPasswordReset(string $to, string $name, string $resetUrl): bool
    {
        $body = "<p>Dear <strong>$name</strong>,</p>
                 <p>You requested a password reset. Click the button below to reset your password:</p>
                 <p style='text-align:center;margin:30px 0'>
                   <a href='$resetUrl' style='background:#2563eb;color:#fff;padding:12px 30px;border-radius:5px;text-decoration:none;font-weight:bold'>Reset Password</a>
                 </p>
                 <p>This link will expire in 1 hour. If you did not request this, please ignore this email.</p>";
        return $this->send($to, 'Password Reset Request', $body, $name);
    }

    public function sendWelcome(string $to, string $name, string $username, string $tempPassword): bool
    {
        $body = "<p>Dear <strong>$name</strong>,</p>
                 <p>Your HRMS account has been created. Here are your login credentials:</p>
                 <table style='margin:20px 0;border-collapse:collapse;width:100%'>
                   <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;width:40%'>Username:</td><td style='padding:8px;border:1px solid #ddd'>$username</td></tr>
                   <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold'>Password:</td><td style='padding:8px;border:1px solid #ddd'>$tempPassword</td></tr>
                 </table>
                 <p><strong>Please change your password after first login.</strong></p>";
        return $this->send($to, 'Your HRMS Account Details', $body, $name);
    }
}

/**
 * File Upload Service
 */
class FileUploadService
{
    private array $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private int $maxSize = 10485760; // 10MB

    public function upload(array $file, string $directory, array $allowedMimes = []): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->uploadErrorMessage($file['error'])];
        }
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed size (10MB)'];
        }
        $mimes = $allowedMimes ?: $this->allowedMimes;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        if (!in_array($realMime, $mimes)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = generateToken(16) . '_' . time() . '.' . strtolower($ext);
        $uploadDir = UPLOAD_PATH . DS . trim($directory, DS);
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $destination = $uploadDir . DS . $newName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }
        return [
            'success'   => true,
            'filename'  => $newName,
            'original'  => safeFilename($file['name']),
            'path'      => $directory . '/' . $newName,
            'mime'      => $realMime,
            'size'      => $file['size'],
        ];
    }

    public function delete(string $path): bool
    {
        $full = UPLOAD_PATH . DS . ltrim($path, DS);
        return file_exists($full) ? unlink($full) : false;
    }

    private function uploadErrorMessage(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        return $errors[$code] ?? 'Unknown upload error';
    }
}

/**
 * Export Service (CSV/Excel)
 */
class ExportService
{
    public function exportCSV(array $data, array $headers, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers);
        foreach ($data as $row) fputcsv($output, $row);
        fclose($output);
        exit;
    }

    public function exportPayslipHTML(array $payroll, array $employee, array $items): string
    {
        ob_start();
        include RESOURCE_PATH . DS . 'views' . DS . 'payroll' . DS . 'payslip_print.php';
        return ob_get_clean() ?: '';
    }
}
