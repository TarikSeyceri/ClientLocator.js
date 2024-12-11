<?php
// Function to get geo data
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
        mapUrl TEXT,
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
            'mapUrl' => $result['mapUrl'],
            'connectionType' => $result['connectionType'],
        ];
    } else {
        // Data not found, fetch from IPStack API
        $accessKey = '8b7a0f896889898da5828c5468a61172';
        $url = "http://api.ipstack.com/{$ipAddress}?access_key={$accessKey}";

        // Make API request
        $response = @file_get_contents($url);

        if ($response === false) {
            return null;
        }

        $geoData = json_decode($response, true);

        if (isset($geoData['country_name'])) {
            // Insert data into the database
            $stmt = $db->prepare("INSERT INTO geo_data (ip, region, country, city, continent, zipCode, mapUrl, connectionType) VALUES (:ip, :region, :country, :city, :continent, :zipCode, :mapUrl, :connectionType)");
            $stmt->execute([
                ':ip' => $ipAddress,
                ':region' => $geoData['region_name'] ?? null,
                ':country' => $geoData['country_name'] ?? null,
                ':city' => $geoData['city'] ?? null,
                ':continent' => $geoData['continent_name'] ?? null,
                ':zipCode' => $geoData['zip'] ?? null,
                ':mapUrl' => "https://www.google.com/maps?q=".$geoData['latitude'].",".$geoData['longitude'],
                ':connectionType' => $geoData['connection_type'] ?? null,
            ]);

            // Return the fetched data
            return [
                'region' => $geoData['region_name'],
                'country' => $geoData['country_name'],
                'city' => $geoData['city'],
                'continent' => $geoData['continent_name'],
                'zipCode' => $geoData['zip'],
                'mapUrl' => "https://www.google.com/maps?q=".$geoData['latitude'].",".$geoData['longitude'],
                'connectionType' => $geoData['connection_type'],
            ];
        } else {
            // API returned an error or data is missing
            return null;
        }
    }
}

// Function to get client IP
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

// Function to ensure all columns exist
function ensureColumnsExist($db, $data) {
    // Get existing columns
    $existingColumns = [];
    $result = $db->query("PRAGMA table_info(trackingData)");
    foreach ($result as $column) {
        $existingColumns[$column['name']] = true;
    }

    // Add missing columns dynamically
    foreach ($data as $key => $value) {
        if (!isset($existingColumns[$key]) && $key !== 'visitorId') {
            $type = is_int($value) ? 'INTEGER' : (is_bool($value) ? 'BOOLEAN' : 'TEXT');
            $db->exec("ALTER TABLE trackingData ADD COLUMN $key $type");
        }
        else if(!isset($existingColumns[$key]) && $key == 'visitorId'){
            $type = 'TEXT';
            $db->exec("ALTER TABLE trackingData ADD COLUMN $key $type");
        }
    }
}

// Get the IP address of the request and current timestamp
$ipAddressRequest = getClientIp();
$serverDateTime = date('Y-m-d H:i:s');

// Get and decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Ensure all columns exist
$data['ipAddressRequest'] = $ipAddressRequest;
$data['serverDateTime'] = $serverDateTime;

if (isset($data['ipAddress'])) {
    $geoData = getGeoData($data['ipAddress']);
}
else {
    $geoData = getGeoData($ipAddressRequest);
}

if ($geoData) {
    $data = array_merge($data, $geoData);
}

// Ensure visitorId exists in input
if (!isset($data['visitorId'])) {
    $data['visitorId'] = uniqid('visitor_', true); // Generate a unique visitorId if not provided
}

// Set up SQLite connection
$db = new PDO('sqlite:data.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create the table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS trackingData (id INTEGER PRIMARY KEY AUTOINCREMENT)");
ensureColumnsExist($db, $data);

// Check if visitorId exists
$stmt = $db->prepare("SELECT COUNT(*) FROM trackingData WHERE visitorId = :visitorId");
$stmt->execute([':visitorId' => $data['visitorId']]);
$exists = $stmt->fetchColumn();

if ($exists) {
    // Update existing row
    $updateColumns = [];
    foreach ($data as $key => $value) {
        if ($key !== 'visitorId') {
            $updateColumns[] = "$key = :$key";
        }
    }
    $updateSql = "UPDATE trackingData SET " . implode(", ", $updateColumns) . " WHERE visitorId = :visitorId";
    $stmt = $db->prepare($updateSql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
} else {
    // Insert new row
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    $insertSql = "INSERT INTO trackingData ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($insertSql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
}

echo json_encode(["status" => "success"]);
?>
