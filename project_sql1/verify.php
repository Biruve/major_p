<?php
error_reporting(0);
include 'db.php';

// Fetch all prompt history
$sql_history_query = "SELECT prompt, response AS generated_sql, model_used FROM prompt_history ORDER BY id DESC LIMIT 10";
$history_result = mysqli_query($conn, $sql_history_query);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SQL Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html {
            font-size: 14px;
        }

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
            background-color: rgb(24, 48, 71);
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
            font-size: 16px;

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

        /* Card styles */
        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            margin: 20px auto;
            max-width: 1000px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
            border-left: 6px solid transparent;
        }

        .card.success {
            border-left-color: #2e7d32;
        }

        .card.error {
            border-left-color: #c62828;
        }

        .card h3 {
            margin-top: 0;
            font-size: 18px;
        }

        /* Pre & SQL */
        pre {
            background: #f4f6f8;
            padding: 12px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            white-space: pre-wrap;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: #fff;
        }

        th,
        td {
            padding: 10px 12px;
            border: 1px solid #ccc;
            text-align: left;
            word-break: break-word;
            max-width: 300px;
            font-size: 13px;
        }

        th {
            background-color: #f1f3f5;
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
        <?php
        if (!$history_result || mysqli_num_rows($history_result) == 0) {
            die("<div class='card error'><h3>No SQL queries found in prompt history.</h3></div>");
        }

        // Loop through all prompt history rows
        while ($row = mysqli_fetch_assoc($history_result)) {
            $generated_sql = $row['generated_sql'];
            $model_used = $row['model_used'];

            $is_dml = preg_match('/^\s*(INSERT|UPDATE|DELETE)\s/i', $generated_sql);
            $is_ddl = preg_match('/^\s*(CREATE|DROP|ALTER)\s/i', $generated_sql);

            if ($is_dml) {
                $dml_result = mysqli_query($conn, $generated_sql);
                $affected_rows = mysqli_affected_rows($conn);

                if ($dml_result) {
                    if ($affected_rows > 0) {
                        // ✅ Query executed and rows affected
                        echo "<div class='card success'>";
                        echo "<h3 style='color:green;'>✅ Correct SQL</h3>";
                        echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                        echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                        echo "<h4>Affected Rows:</h4><pre>$affected_rows</pre>";
                        echo "</div>";
                    } else {
                        // ✅ Correct SQL but no rows matched
                        echo "<div class='card success'>";
                        echo "<h3 style='color:green;'>✅ Correct SQL</h3>";
                        echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                        echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                        echo "<h4>Error:</h4><pre>No rows were affected.</pre>";
                        echo "</div>";
                    }
                } else {
                    // ❌ SQL syntax or execution error
                    echo "<div class='card error'>";
                    echo "<h3 style='color:red;'>❌ Incorrect SQL</h3>";
                    echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                    echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                    echo "<h4>Error:</h4><pre>" . mysqli_error($conn) . "</pre>";
                    echo "</div>";
                }

                continue; // ✅ Prevent further processing after handling DML
            }

            if ($is_ddl) {
                $ddl_result = mysqli_query($conn, $generated_sql);

                if ($ddl_result) {
                    echo "<div class='card success'>";
                    echo "<h3 style='color:green;'>✅ Correct SQL</h3>";
                    echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                    echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                    echo "<h4>Query executed successfully.</h4>";
                    echo "</div>";
                } else {
                    echo "<div class='card error'>";
                    echo "<h3 style='color:red;'>❌ Incorrect SQL</h3>";
                    echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                    echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                    echo "<h4>Error:</h4><pre>" . mysqli_error($conn) . "</pre>";
                    echo "</div>";
                }

                continue; // ✅ Prevent further processing after handling DDL
            }
            $result = mysqli_query($conn, $generated_sql);

            if ($result === true) {
                echo "<div class='card success'>";
                echo "<h3 style='color:green;'>✅ Correct SQL</h3>";
                echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                echo "<h4>Executed SQL:</h4><pre>$generated_sql</pre>";
                echo "<h4>Query executed successfully.</h4>";
                echo "</div>";
            } elseif ($result && is_object($result) && mysqli_num_rows($result) > 0) {
                echo "<div class='card success'>";
                echo "<h3 style='color:green;'>✅ Correct SQL</h3>";
                echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                echo "<h4>Executed SQL:</h4><pre>$generated_sql</pre>";
                echo "<h4>Result:</h4>";

                echo "<table><tr>";
                while ($field = mysqli_fetch_field($result)) {
                    echo "<th>{$field->name}</th>";
                }
                echo "</tr>";

                while ($row_data = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    foreach ($row_data as $val) {
                        echo "<td>" . htmlspecialchars($val) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='card error'>";
                echo "<h3 style='color:red;'>❌ Incorrect SQL</h3>";
                echo "<h4>Model Used:</h4><pre>" . htmlspecialchars($model_used) . "</pre>";
                echo "<h4>Executed SQL:</h4><pre>" . htmlspecialchars($generated_sql) . "</pre>";
                echo "<h4>Error:</h4><pre style='margin-bottom:0;'>The SQL query is invalid or could not be executed.</pre>";
                echo "</div>";
            }

            // $error_msg = mysqli_error($conn);
            // if (strpos($error_msg, 'Unknown column') !== false || strpos($error_msg, 'doesn\'t exist') !== false) {
            //     echo "<h4>Error:</h4><pre>Invalid table or column: " . htmlspecialchars($error_msg) . "</pre>";
            // } else {
            //     echo "<h4>Error:</h4><pre>" . htmlspecialchars($error_msg) . "</pre>";
            // }
        }
        ?>
    </div> <!-- end main-content -->
</body>

</html>


<!-- ALTER TABLE employees DROP INDEX unique_employee; -->