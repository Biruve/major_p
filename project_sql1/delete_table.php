<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleteCategory = $_POST['delete_table'] ?? '';
    if (!empty($deleteCategory)) {
        $stmt = $conn->prepare("DELETE FROM language_category WHERE category_name = ?");
        $stmt->bind_param("s", $deleteCategory);
        if ($stmt->execute()) {
            header("Location: select_table.php?msg=Category+deleted+successfully");
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
    <title>Delete Category</title>
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
            background-color: #212529;
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
<!-- <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">TestHub</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="select_table.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="delete_category.php">Delete Category</a>
                </li>
            </ul>
        </div>
    </div>
</nav> -->

<!-- Form Card -->
<div class="container mt-5">
    <div class="card">
        <div class="card-header">Delete Category</div>
        <div class="card-body">
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="delete_table" class="form-label">Select Table</label>
                    <select name="delete_table" id="delete_table" class="form-select" required>
                        <option value="">Select...</option>
                        <?php
                        $result = $conn->query("SELECT category_name FROM language_category");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['category_name']) . "'>" . htmlspecialchars($row['category_name']) . "</option>";
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
