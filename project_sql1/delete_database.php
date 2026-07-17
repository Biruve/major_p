<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleteDatabase = $_POST['delete_db'] ?? '';

    if (!empty($deleteDatabase)) {
        $stmt = $conn->prepare("DELETE FROM create_db WHERE db_name = ?");
        $stmt->bind_param("s", $deleteDatabase);

        if ($stmt->execute()) {
            header("Location: select_table.php?msg=Database+deleted+successfully");
            exit;
        } else {
            $error = "❌ Error deleting category.";
        }
    } else {
        $error = "⚠️ No category selected to delete.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #343a40;
            color: white;
            border-bottom: 2px solid #dee2e6;
        }
        .form-select {
            border-radius: 8px;
        }
        .btn-danger {
            border-radius: 8px;
        }
        .btn-secondary {
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Delete Database</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="delete_db" class="form-label">Select Database</label>
                    <select name="delete_db" id="delete_db" class="form-select" required>
                        <option value="">Select...</option>
                        <?php
                        $result = $conn->query("SELECT db_name FROM create_db");
                        while ($row = $result->fetch_assoc()) {
                            $dbName = htmlspecialchars($row['db_name']);
                            echo "<option value='$dbName'>$dbName</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger">Delete</button>
                <a href="select_table.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
