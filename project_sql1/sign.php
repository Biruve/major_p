<?php
require 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password']; // plain text password (no hashing)
    $branch = $_POST['branch'];
    $semester = $_POST['semester'];
    $usn = $_POST['usn'];

    $stmt = $conn->prepare("INSERT INTO login (username, password, branch, semester, usn) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $password, $branch, $semester, $usn);

    if ($stmt->execute()) {
        header("Location: index.php");
    } else {
        $error = "Signup failed! Username or USN might already exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4 mx-auto" style="max-width: 500px;">
        <h4 class="text-center mb-3">Sign Up</h4>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Branch <span class="text-danger">*</span></label>
                <input type="text" name="branch" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Semester <span class="text-danger">*</span></label>
                <select name="semester" class="form-select" required>
                    <option value="">-- Select Semester --</option>
                    <?php for ($i = 1; $i <= 8; $i++) echo "<option value='Sem $i'>Sem $i</option>"; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">USN <span class="text-danger">*</span></label>
                <input type="text" name="usn" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
            <p class="mt-3 text-center"><a href="index.php">Already have an account?</a></p>
        </form>
    </div>
</div>
</body>
</html>
