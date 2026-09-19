<?php 
require_once 'config/db.php';

header('Content-Type: application/json');

if (isset($_POST['field']) && isset($_POST['value'])) {
    $field = $_POST['field'];
    $value = trim($_POST['value']);

    // Whitelist valid columns in the `users` table
    if (!in_array($field, ['email'])) {
        echo json_encode(['exists' => false]);
        exit;
    }

    // Query updated to target `users` table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $field = :value");
    $stmt->execute(['value' => $value]);
    $count = $stmt->fetchColumn();

    echo json_encode(['exists' => ($count > 0)]);
}
?>