<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['invoice_id'])) {
        die("Invoice ID is missing.");
    }
    
    $invoice_id = (int)$_POST['invoice_id'];

    $issue_date = $_POST['issue_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'new';
    $bill_to_name = $_POST['bill_to_name'] ?? '';
    $bill_to_email = $_POST['bill_to_email'] ?? '';
    $bill_to_town_city = $_POST['bill_to_town_city'] ?? '';
    $bill_to_region_country = $_POST['bill_to_region_country'] ?? '';
    $bill_to_phone = $_POST['bill_to_phone'] ?? '';
    $project_amount = $_POST['project_amount'] ?? 0;
    $total_due = $_POST['total_due'] ?? 0;
    $discount = $_POST['discount'] ?? 0;
    $net_total = $_POST['net_total'] ?? 0;
    $deposit_percentage = $_POST['deposit_percentage'] ?? 0;
    $deposit_amount = $_POST['deposit_amount'] ?? 0;
    $deposit_status = $_POST['deposit_status'] ?? 'pending';
    $deposit_paid_date = !empty($_POST['deposit_paid_date']) ? $_POST['deposit_paid_date'] : null;
    $balance_remaining = $_POST['balance_remaining'] ?? 0;

    try {
        $pdo->beginTransaction();

        // 1. Update main invoice record
        $stmt = $pdo->prepare("
            UPDATE invoices SET 
                issue_date = :issue_date,
                due_date = :due_date,
                status = :status,
                bill_to_name = :bill_name,
                bill_to_email = :bill_email,
                bill_to_town_city = :bill_town,
                bill_to_region_country = :bill_region,
                bill_to_phone = :bill_phone,
                project_amount = :project_amount,
                total_due = :total_due,
                discount = :discount,
                net_total = :net_total,
                deposit_percentage = :deposit_percentage,
                deposit_amount = :deposit_amount,
                deposit_status = :deposit_status,
                deposit_paid_date = :deposit_paid_date,
                balance_remaining = :balance_remaining
            WHERE id = :id
        ");

        $stmt->execute([
            ':issue_date' => $issue_date,
            ':due_date' => $due_date,
            ':status' => $status,
            ':bill_name' => $bill_to_name,
            ':bill_email' => $bill_to_email,
            ':bill_town' => $bill_to_town_city,
            ':bill_region' => $bill_to_region_country,
            ':bill_phone' => $bill_to_phone,
            ':project_amount' => $project_amount,
            ':total_due' => $total_due,
            ':discount' => $discount,
            ':net_total' => $net_total,
            ':deposit_percentage' => $deposit_percentage,
            ':deposit_amount' => $deposit_amount,
            ':deposit_status' => $deposit_status,
            ':deposit_paid_date' => $deposit_paid_date,
            ':balance_remaining' => $balance_remaining,
            ':id' => $invoice_id
        ]);

        // 2. Delete existing items for this invoice
        $deleteStmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = :id");
        $deleteStmt->execute([':id' => $invoice_id]);

        // 3. Insert fresh items
        if (!empty($_POST['item_desc'])) {
            $itemStmt = $pdo->prepare("
                INSERT INTO invoice_items 
                (invoice_id, description, quantity, rate, amount) 
                VALUES 
                (:invoice_id, :description, :quantity, :rate, :amount)
            ");

            foreach ($_POST['item_desc'] as $index => $desc) {
                $note = $_POST['item_note'][$index] ?? '';
                $qty = $_POST['item_qty'][$index] ?? 1;
                $rate = $_POST['item_rate'][$index] ?? 0;
                
                $full_desc = trim($desc);
                if (!empty($note)) {
                    $full_desc .= "||" . trim($note);
                }

                $amount = $qty * $rate;

                $itemStmt->execute([
                    ':invoice_id' => $invoice_id,
                    ':description' => $full_desc,
                    ':quantity' => $qty,
                    ':rate' => $rate,
                    ':amount' => $amount
                ]);
            }
        }

        $pdo->commit();

        session_start();
        $_SESSION['notify_msg'] = "Invoice updated successfully!";

        header("Location: preview.php?id=" . $invoice_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error updating invoice: " . $e->getMessage());
    }
}
?>
