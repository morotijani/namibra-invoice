<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'pdf_generator.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendInvoiceEmail($invoice_id, $pdo, $subject, $bodyMsg) {
    // Fetch invoice details
    $stmt = $pdo->prepare("SELECT invoice_no, bill_to_name, bill_to_email FROM invoices WHERE id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice || empty($invoice['bill_to_email'])) {
        return ['status' => false, 'error' => 'No email address found for this invoice.'];
    }

    $pdfContent = generateInvoicePDF($invoice_id, $pdo);
    if (!$pdfContent) return ['status' => false, 'error' => 'Failed to generate PDF.'];

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = (SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, 'Namibra Software Technologies');
        $mail->addAddress($invoice['bill_to_email'], $invoice['bill_to_name']);

        // Attachments
        $fileName = $invoice['invoice_no'] . '.pdf';
        $mail->addStringAttachment($pdfContent, $fileName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; color: #14201b;'>
                <h2>Hello {$invoice['bill_to_name']},</h2>
                <p>{$bodyMsg}</p>
                <p>Please find your invoice <strong>{$invoice['invoice_no']}</strong> attached to this email as a PDF.</p>
                <br/>
                <p>Best regards,<br/>Namibra Software Technologies</p>
            </div>
        ";
        $mail->AltBody = "Hello {$invoice['bill_to_name']},\n\n{$bodyMsg}\n\nPlease find your invoice attached.\n\nBest regards,\nNamibra Software Technologies";

        $mail->send();
        return ['status' => true, 'error' => null];
    } catch (Exception $e) {
        $error = $mail->ErrorInfo;
        error_log("Message could not be sent. Mailer Error: {$error}");
        return ['status' => false, 'error' => $error];
    }
}

function sendInvoiceSMS($invoice_id, $pdo, $type = 'new') {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice || empty($invoice['bill_to_phone'])) {
        return ['status' => false, 'error' => 'No phone number found.'];
    }

    $phone = $invoice['bill_to_phone'];
    // Clean phone number: remove spaces, +, etc if Arkesel requires, though Arkesel handles standard international formats usually.
    // For safety, we just pass what the user inputted assuming it's valid.

    if ($type === 'new') {
        $amount = number_format($invoice['total_due'], 2);
        $date = date('d M Y', strtotime($invoice['due_date']));
        $message = "Hello {$invoice['bill_to_name']}, your new invoice {$invoice['invoice_no']} for GH₵ {$amount} is ready. Due date: {$date}. Namibra Software.";
    } else {
        $status = strtoupper($invoice['status']);
        $message = "Hello {$invoice['bill_to_name']}, the status of your invoice {$invoice['invoice_no']} has been updated to: {$status}. Namibra Software.";
    }

    $data = [
        'sender' => ARKESEL_SENDER_ID,
        'message' => $message,
        'recipients' => [$phone]
    ];

    $ch = curl_init('https://sms.arkesel.com/api/v2/sms/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . ARKESEL_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 || $httpCode == 201) {
        $respData = json_decode($response, true);
        if (isset($respData['status']) && $respData['status'] !== 'success' && $respData['status'] !== true) {
            return ['status' => false, 'error' => 'Arkesel: ' . ($respData['message'] ?? 'Unknown error')];
        }
        return ['status' => true, 'error' => null];
    }
    
    return ['status' => false, 'error' => "HTTP {$httpCode}: " . $response];
}
?>
