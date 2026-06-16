<?php
require 'config.php';
require 'pdf_generator.php';

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$invoice_id = (int)$_GET['id'];
$pdfContent = generateInvoicePDF($invoice_id, $pdo);

if (!$pdfContent) {
    die("Invoice not found.");
}

$stmt = $pdo->prepare("SELECT invoice_no FROM invoices WHERE id = :id");
$stmt->execute([':id' => $invoice_id]);
$invoice = $stmt->fetch();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $invoice['invoice_no'] . '.pdf"');
echo $pdfContent;
exit;
?>
