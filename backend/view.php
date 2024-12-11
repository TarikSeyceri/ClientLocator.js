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

// Check if the Empty Table button was clicked
if (isset($_POST['emptyTable'])) {
    // Prepare and execute the query to delete all data from the table
    $stmt = $db->prepare("DELETE FROM trackingData");
    $stmt->execute();

    // Redirect to the same page to refresh the data after deletion
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all column names dynamically
$columns = [];
$result = $db->query("PRAGMA table_info(trackingData)");
foreach ($result as $column) {
    $columns[] = $column['name'];
}

// Fetch all data from the trackingData table
$stmt = $db->query("SELECT * FROM trackingData");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function wrapUrlInAnchor($str) {
    $str = htmlspecialchars($str);

    // Check if the string contains "http:" or "https:"
    if (preg_match('/\bhttps?:\/\/\S+/i', $str)) {
        // Wrap the URL with <a> tag
        return '<a href="' . $str . '" target="_blank">' . $str . '</a>';
    }
    // Return the string as it is if no URL is found
    return $str;
}
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

        .empty-table-btn {
            background-color: #f44336; /* Red background */
            color: white; /* White text */
            font-size: 16px; /* Font size */
            padding: 10px 20px; /* Padding */
            border: none; /* Remove border */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s ease; /* Smooth transition for background color */
            font-weight: bold; /* Bold text */
        }
        .empty-table-btn:hover {
            background-color: #d32f2f; /* Darker red on hover */
        }
        .empty-table-btn:active {
            background-color: #b71c1c; /* Even darker red when clicked */
        }
    </style>
</head>
<body>
    <form method="POST" action="">
        <button type="submit" name="emptyTable" class="empty-table-btn" onclick="return confirm('Are you sure you want to empty the table?');">Empty Table</button>
    </form>
    
    <br/>

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
                echo "<td>" . wrapUrlInAnchor($row[$column] ?? '') . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>
