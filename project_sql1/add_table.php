<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCategory = trim($_POST['new_category'] ?? '');

    if (!empty($newCategory)) {
        // Check for existing category
        $checkStmt = $conn->prepare("SELECT id FROM language_category WHERE category_name = ?");
        $checkStmt->bind_param("s", $newCategory);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "⚠️ Category '$newCategory' already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO language_category (category_name) VALUES (?)");
            $stmt->bind_param("s", $newCategory);
            if ($stmt->execute()) {
                header("Location: select_table.php?msg=Category+added+successfully");
                exit;
            } else {
                $error = "❌ Error adding category.";
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
            background-color:rgb(14, 29, 51);
            color: white;
            font-weight: 500;
        }
        .btn {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<!-- <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">TestHub</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="select_table.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_category.php">Add Table</a>
                </li>
            </ul>
        </div>
    </div>
</nav> -->

<!-- Form Card -->
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            Add New Table
        </div>
        <div class="card-body">
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="new_table" class="form-label">Table Name</label>
                    <input type="text" class="form-control" name="new_category" id="new_category" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Table</button>
                <a href="select_table.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
