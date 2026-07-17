<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['created_at'])) {
    $user_id = $_SESSION['login_id'] ?? 1;
    $created_at = $_POST['created_at'];

    $stmt = $conn->prepare("DELETE FROM prompt_history WHERE user_id = ? AND created_at = ?");
    $stmt->bind_param("is", $user_id, $created_at);
    $stmt->execute();
}
?>
