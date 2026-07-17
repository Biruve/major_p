<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nl = $_POST['nl_query'] ?? '';
    $sql = $_POST['correct_sql'] ?? '';

    if ($nl && $sql) {
        $stmt = $conn->prepare("INSERT INTO sql_dataset (nl_query, correct_sql) VALUES (?, ?)");
        $stmt->bind_param("ss", $nl, $sql);
        $stmt->execute();
        echo "Entry added!";
    }
}
?>

<form method="POST">
    <label>Natural Language Query:<br><textarea name="nl_query" rows="3" cols="50"></textarea></label><br>
    <label>Correct SQL:<br><textarea name="correct_sql" rows="3" cols="50"></textarea></label><br>
    <button type="submit">Add Entry</button>
</form>
