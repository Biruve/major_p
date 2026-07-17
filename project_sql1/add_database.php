<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDatabase = trim($_POST['new_database'] ?? '');

    if (!empty($newDatabase)) {
        // Check for existing category
        $checkStmt = $conn->prepare("SELECT id FROM create_db WHERE db_name = ?");
        $checkStmt->bind_param("s", $newDatabase);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "⚠️ Category '$newDatabase' already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO create_db (db_name) VALUES (?)");
            $stmt->bind_param("s", $newDatabase);
            if ($stmt->execute()) {
                header("Location: select_table.php?msg=Database+added+successfully");
                exit;
            } else {
                $error = "❌ Error adding database.";
            }
        }
    } else {
        $error = "⚠️ Category name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Category</title>
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
            background-color:rgb(16, 38, 71);
            color: white;
            font-weight: 500;
        }
        .btn {
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            Add New Database
        </div>
        <div class="card-body">
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="new_database" class="form-label">Database Name</label>
                    <input type="text" class="form-control" name="new_database" id="new_database" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Database</button>
                <a href="select_table.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
