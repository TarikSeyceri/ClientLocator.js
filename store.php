<?php
// Set up SQLite connection
$db = new PDO('sqlite:data.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to check and create columns if they don't exist
function ensureColumnsExist($db, $data) {
    // Get existing columns
    $existingColumns = [];
    $result = $db->query("PRAGMA table_info(trackingData)");
    foreach ($result as $column) {
        $existingColumns[$column['name']] = true;
    }

    // Add missing columns dynamically
    foreach ($data as $key => $value) {
        if (!isset($existingColumns[$key])) {
            $type = is_int($value) ? 'INTEGER' : (is_bool($value) ? 'BOOLEAN' : 'TEXT');
            $db->exec("ALTER TABLE trackingData ADD COLUMN $key $type");
        }
    }
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);  // First IP in the list is the client IP
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getGeoData($ipAddress) {
    // Database setup
    $db = new PDO('sqlite:geoData.db'); // Path to your SQLite database file
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS geo_data (
        ip TEXT PRIMARY KEY,
        region TEXT,
        country TEXT,
        city TEXT,
        continent TEXT,
        zipCode TEXT,
        connectionType TEXT
    )");

    // Check if IP data exists in the database
    $stmt = $db->prepare("SELECT * FROM geo_data WHERE ip = :ip");
    $stmt->execute([':ip' => $ipAddress]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Data exists, return from the database
        return [
            'region' => $result['region'],
            'country' => $result['country'],
            'city' => $result['city'],
            'continent' => $result['continent'],
            'zipCode' => $result['zipCode'],
            'connectionType' => $result['connectionType'],
        ];
    } else {
        // Data not found, fetch from IPStack API
        $accessKey = '8b7a0f896889898da5828c5468a61172';
        $url = "http://api.ipstack.com/{$ipAddress}?access_key={$accessKey}";

        // Make API request
        $response = file_get_contents($url);
        $geoData = json_decode($response, true);

        if (isset($geoData['region_name'])) {
            // Insert data into the database
            $stmt = $db->prepare("INSERT INTO geo_data (ip, region, country, city, continent, zipCode, connectionType) VALUES (:ip, :region, :country, :city, :continent, :zipCode, :connectionType)");
            $stmt->execute([
                ':ip' => $ipAddress,
                ':region' => $geoData['region_name'] ?? null,
                ':country' => $geoData['country_name'] ?? null,
                ':city' => $geoData['city'] ?? null,
                ':continent' => $geoData['continent_name'] ?? null,
                ':zipCode' => $geoData['zip'] ?? null,
                ':connectionType' => $geoData['connection_type'] ?? null,
            ]);

            // Return the fetched data
            return [
                'region' => $geoData['region_name'],
                'country' => $geoData['country_name'],
                'city' => $geoData['city'],
                'continent' => $geoData['continent_name'],
                'zipCode' => $geoData['zip'],
                'connectionType' => $geoData['connection_type'],
            ];
        } else {
            // API returned an error or data is missing
            return null;
        }
    }
}

// Create the table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS trackingData (id INTEGER PRIMARY KEY AUTOINCREMENT)");

// Get the IP address of the request and current timestamp
$ipAddressRequest = getClientIp();
$serverDateTime = date('Y-m-d H:i:s');

// Get and decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Ensure all columns exist
$data['ipAddressRequest'] = $ipAddressRequest;
$data['serverDateTime'] = $serverDateTime;

if(isset($data['ipAddress'])) {
    $geoData = getGeoData($data['ipAddress']);
    if ($geoData) {
        $data = array_merge($data, $geoData);
    }
}
else {
    $geoData = getGeoData($ipAddressRequest);
    if ($geoData) {
        $data = array_merge($data, $geoData);
    }
}

ensureColumnsExist($db, $data);

// Dynamically build the SQL statement for insertion
$columns = implode(", ", array_keys($data));
$placeholders = ":" . implode(", :", array_keys($data));
$sql = "INSERT INTO trackingData ($columns) VALUES ($placeholders)";

$stmt = $db->prepare($sql);

// Bind parameters dynamically
foreach ($data as $key => $value) {
    $stmt->bindValue(":$key", $value);
}

// Execute the statement
$stmt->execute();

echo json_encode(["status" => "success"]);
