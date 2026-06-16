<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['id']) && isset($data['status'])) {
        $id = (int)$data['id'];
        $status = $data['status'];

        $allowed_statuses = ['new', 'pending', 'partial payment', 'full paid', 'completed'];
        
        if (in_array($status, $allowed_statuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE invoices SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $status, ':id' => $id]);
                echo json_encode(['success' => true]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
