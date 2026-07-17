<?php
require 'db.php';
session_start();

$usedTable = '';
$msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Table'], $_POST['Database'])) {
    $selectedTable = trim($_POST['Table']);
    $selectedDB = trim($_POST['Database']);

    // Sanitize table name
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $selectedTable)) {
        $msg = "❌ Invalid table name.";
    } else {
        if (!mysqli_select_db($conn, $selectedDB)) {
            $msg = "❌ Database selection failed.";
        } else {
            // Check if table already exists
            $exists = $conn->query("SHOW TABLES LIKE '$selectedTable'");
            if ($exists->num_rows === 0) {
                $columnsSQL = [];
                if (isset($_POST['column_name']) && is_array($_POST['column_name'])) {
                    $colNames = $_POST['column_name'];
                    $colTypes = $_POST['column_type'];
                    $colLengths = $_POST['column_length'];
                    $primaryKeyIndex = isset($_POST['primary_key']) ? (int) $_POST['primary_key'] : -1;
                    $notNulls = isset($_POST['not_null']) ? $_POST['not_null'] : [];

                    for ($i = 0; $i < count($colNames); $i++) {
                        $colName = trim($colNames[$i]);
                        $colType = strtoupper(trim($colTypes[$i]));
                        $length = trim($colLengths[$i]);
                        $isPrimary = ($primaryKeyIndex === $i);
                        $isNotNull = in_array($i, $notNulls);

                        if ($colName !== '') {
                            $sqlType = $colType;
                            if ($colType === 'VARCHAR' && $length !== '') {
                                $sqlType .= "($length)";
                            } elseif ($colType === 'INT' && $length !== '') {
                                $sqlType .= "($length)";
                            }

                            $column = "`$colName` $sqlType";
                            if ($isNotNull) {
                                $column .= " NOT NULL";
                            }
                            if ($isPrimary) {
                                $column .= " PRIMARY KEY";
                            }

                            $columnsSQL[] = $column;
                        }
                    }
                }

                if (!empty($columnsSQL)) {
                    $columnsSQLStr = implode(", ", $columnsSQL);
                    $createSQL = "CREATE TABLE `$selectedTable` ($columnsSQLStr)";
                    if ($conn->query($createSQL)) {
                        $msg = "✅ Table '$selectedTable' created successfully.";
                        $usedTable = $selectedTable;
                    } else {
                        $msg = "❌ Failed to create table: " . $conn->error;
                    }
                } else {
                    $msg = "❌ No valid columns provided.";
                }
            } else {
                $msg = "ℹ️ Table '$selectedTable' already exists.";
                $usedTable = $selectedTable;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Select Database & Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            display: flex;
        }

        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            /* Full height */
            min-width: 200px;
            max-width: 200px;
            background-color:rgb(24, 48, 71);
            padding-top: 20px;
            overflow-y: auto;
            /* Allow scroll if content exceeds height */
        }


        #sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            font-weight: bold;

        }

        #sidebar a:hover {
            background-color: #495057;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
        }

        .main-content {
            margin-left: 200px;
            /* Same as sidebar width */
            padding: 20px;
            flex: 1;
        }
        
    </style>
    
</head>

<body>
   
    <!-- Sidebar -->
    <div id="sidebar" position="fixed">
        <a href="select_table.php">DB_CREATOR</a>
        <a href="prompt.php">PROMPT</a>
        <a href="verify.php">VERIFY</a>
        <a href="logout.php">LOGOUT</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Select Database and Tables</h2>
        <form action="select_table.php" method="post">
            <div class="row mb-4">
                <!-- <div class="col-md-12">
                    <label for="categorySelect" class="form-label">Select Category</label>
                    <select id="categorySelect" name="Category" class="form-control" onchange="runQuery()" required>
                        <option value="mysql">MySQL</option>
                    </select> -->

                    <div class="row mb-4">
                        <div class="col-md-4 mt-4">
                            <label for="SelectDatabase" class="form-label">Select Database</label>
                            <select id="SelectDatabase" name="Database" class="form-control" required>
                                <option value="">Select Database</option>
                                <?php
                                $queryDB = mysqli_query($conn, "SELECT * FROM create_db");
                                while ($rowDB = mysqli_fetch_array($queryDB)) { ?>
                                    <option value="<?= htmlspecialchars($rowDB['db_name']) ?>">
                                        <?= htmlspecialchars($rowDB['db_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2 mt-4">
                            <a href="add_database.php" class="btn btn-primary me-1"><i class="bi bi-plus"></i></a>
                            <a href="delete_database.php" class="btn btn-danger"><i class="bi bi-dash"></i></a>
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="SelectTable" class="form-label">Select Table</label>
                            <select id="SelectTable" name="Table" class="form-control" required>
                                <option value="">Select Table</option>
                                <?php
                                $query1 = mysqli_query($conn, "SELECT * FROM language_category");
                                while ($row1 = mysqli_fetch_array($query1)) { ?>
                                    <option value="<?= htmlspecialchars($row1['category_name']) ?>">
                                        <?= htmlspecialchars($row1['category_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2 mt-4">
                            <a href="add_table.php" class="btn btn-primary me-1"><i class="bi bi-plus"></i></a>
                            <a href="delete_table.php" class="btn btn-danger"><i class="bi bi-dash"></i></a>
                        </div>
                    </div>

                    <!-- Column Table -->
                    <div>
                        <h5 style="margin-top: 20px;">Add Table Columns</h5>
                        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 10px;"
                            id="columnTable">
                            <thead style="background-color: #f2f2f2;">
                                <tr>
                                    <th>Column Name</th>
                                    <th>Type</th>
                                    <th>Length</th>
                                    <th>Not Null</th>
                                    <th>Primary</th>
                                    <th>Auto Increment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="column_name[]" placeholder="Enter column name"></td>
                                    <td>
                                        <select name="column_type[]">
                                            <option value="INT">INT</option>
                                            <option value="VARCHAR">VARCHAR</option>
                                            <option value="TEXT">TEXT</option>
                                            <option value="DATE">DATE</option>
                                            <option value="TIME">TIME</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="column_length[]" placeholder="Length (e.g. 50)"></td>
                                    <td style="text-align: center;"><input type="checkbox" name="not_null[]" value="0">
                                    </td>
                                    <td style="text-align: center;"><input type="radio" name="primary_key" value="0">
                                    </td>
                                    <td style="text-align: center;"><input type="checkbox" name="auto_increment[]" value="0"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm"
                                            onclick="removeRow(this)">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                            <button type="button" onclick="addRow()">+ Add Column</button>
                        </div>
                    </div>

                    <div class="mt-1">
                        <button type="submit" class="btn btn-primary">Create Table</button>
                    </div>
                </div>
            <!-- </div> -->
        </form>
   
        <?php
        if (!empty($usedTable)) {
            echo "<h4 class='mt-5'>Structure of table <strong>$usedTable</strong></h4>";
            $structure = $conn->query("DESCRIBE `$usedTable`");
            echo "<table class='table table-sm'><thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead><tbody>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
            }
            echo "</tbody></table>";
        }

        if (!empty($msg)) {
            echo "<div class='alert alert-info mt-3'>$msg</div>";
        }
        ?>
    </div>

    <script>
        function addRow() {
            const table = document.getElementById("columnTable").getElementsByTagName('tbody')[0];
            const rowCount = table.rows.length;
            const row = table.insertRow();

            row.innerHTML = `
        <td><input type="text" name="column_name[]" placeholder="Enter column name" required></td>
        <td>
            <select name="column_type[]">
                <option value="INT">INT</option>
                <option value="VARCHAR">VARCHAR</option>
                <option value="TEXT">TEXT</option>
                <option value="DATE">DATE</option>
                <option value="TIME">TIME</option>
            </select>
        </td>
        <td><input type="text" name="column_length[]" placeholder="Length (e.g. 50)"></td>
        <td style="text-align: center;"><input type="checkbox" name="not_null[]" value="${rowCount}"></td>
        <td style="text-align: center;"><input type="radio" name="primary_key" value="${rowCount}"></td>
        <td style="text-align: center;"><input type="checkbox" name="auto_increment[]" value="${rowCount}"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
    `;
        }

        function removeRow(button) {
            const row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }
    </script>

</body>

</html>