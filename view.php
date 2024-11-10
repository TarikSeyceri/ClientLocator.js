<?php
// Include configuration
include 'config.php';

// Basic authentication
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== USERNAME || $_SERVER['PHP_AUTH_PW'] !== PASSWORD) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized access';
    exit;
}

// Set up SQLite connection
$db = new PDO('sqlite:data.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch all column names dynamically
$columns = [];
$result = $db->query("PRAGMA table_info(trackingData)");
foreach ($result as $column) {
    $columns[] = $column['name'];
}

// Fetch all data from the trackingData table
$stmt = $db->query("SELECT * FROM trackingData");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <?php
            // Display column headers dynamically
            foreach ($columns as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            ?>
        </tr>
        <?php
        // Display each row of data
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($columns as $column) {
                echo "<td>" . htmlspecialchars($row[$column] ?? '') . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>
