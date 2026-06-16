<?php
require 'config.php';
require 'notifier.php';

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$invoice_id = (int)$_GET['id'];

$emailResult = sendInvoiceEmail(
    $invoice_id, 
    $pdo, 
    "New Invoice from Namibra Software Technologies", 
    "Your new invoice has been generated."
);

$smsResult = sendInvoiceSMS($invoice_id, $pdo, 'new');

// Redirect back with a status message
session_start();

$msg = "Notifications triggered! ";
$msg .= "Email: " . ($emailResult['status'] ? 'Sent' : 'Failed (' . $emailResult['error'] . ')');
$msg .= " | SMS: " . ($smsResult['status'] ? 'Sent' : 'Failed (' . $smsResult['error'] . ')');

$_SESSION['notify_msg'] = $msg;

header("Location: preview.php?id=" . $invoice_id);
exit;
?>
