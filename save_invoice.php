<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $balance_status = $_POST['balance_status'] ?? 'pending';
    $balance_paid_date = !empty($_POST['balance_paid_date']) ? $_POST['balance_paid_date'] : null;

    try {
        $pdo->beginTransaction();

        // 1. Insert main invoice record with a temporary invoice_no
        $stmt = $pdo->prepare("
            INSERT INTO invoices 
            (invoice_no, issue_date, due_date, status, bill_to_name, bill_to_email, bill_to_town_city, bill_to_region_country, bill_to_phone, project_amount, total_due, discount, net_total, deposit_percentage, deposit_amount, deposit_status, deposit_paid_date, balance_remaining, balance_status, balance_paid_date) 
            VALUES 
            ('TEMP', :issue_date, :due_date, :status, :bill_name, :bill_email, :bill_town, :bill_region, :bill_phone, :project_amount, :total_due, :discount, :net_total, :deposit_percentage, :deposit_amount, :deposit_status, :deposit_paid_date, :balance_remaining, :balance_status, :balance_paid_date)
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
            ':balance_status' => $balance_status,
            ':balance_paid_date' => $balance_paid_date
        ]);

        $invoice_id = $pdo->lastInsertId();

        // 2. Generate and update real invoice_no
        $year = date('Y', strtotime($issue_date));
        $formatted_id = str_pad($invoice_id, 4, '0', STR_PAD_LEFT);
        $invoice_no = "NST-$year-$formatted_id"; // Matches template e.g. NST-2026-0054

        $updateStmt = $pdo->prepare("UPDATE invoices SET invoice_no = :invoice_no WHERE id = :id");
        $updateStmt->execute([':invoice_no' => $invoice_no, ':id' => $invoice_id]);

        // 3. Insert items
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
                    $full_desc .= "||" . trim($note); // We use || to separate desc and note for easy splitting later
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

        header("Location: preview.php?id=" . $invoice_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error saving invoice: " . $e->getMessage());
    }
}
?>
