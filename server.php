<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spamprotection";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Allow CORS
allowCORS();

try {
    checkConnection(true);
    createSpamReportTable();
    handleAPIRequests($conn);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}

function allowCORS() {
    // Allow requests from any origin
    header('Access-Control-Allow-Origin: *');
    // Allow the GET and PUT methods from any origin
    header('Access-Control-Allow-Methods: GET, PUT');
    // Allow the Content-Type header in requests
    header('Access-Control-Allow-Headers: Content-Type');
    // Set content type
    header('Content-Type: application/json');
}

function checkConnection($initialCheck = false) {
    global $conn;

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Log success message only during the initial connection check
    if ($initialCheck) {
        error_log('Connected to the database successfully.');
    }
}

function createSpamReportTable() {
    global $conn;

    $sql = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255),
        content TEXT,
        blocked BOOLEAN DEFAULT 0,
        resolved BOOLEAN DEFAULT 0,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql) === false) {
        throw new Exception("Error creating table: " . $conn->error);
    }
}

function handleAPIRequests($conn) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($conn);
            break;
        case 'PUT':
            handlePutRequest($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid request method']);
            break;
    }
}

function handleGetRequest($conn) {
    $result = $conn->query("SELECT * FROM reports WHERE state IN ('OPEN', 'CLOSED') ORDER BY id DESC");
    $reports = [];

    if ($result) {
        // Check if there are rows in the result set
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
        }
        // Free the result set
        $result->free();
    } else {
        // Handle the case where the query fails
        echo json_encode(['error' => 'Error executing query: ' . $conn->error]);
        return;
    }

    // Create an associative array with the desired name
    $response = ['reports' => $reports];

    // Encode and echo the response
    echo json_encode($response);
}

function handlePutRequest($conn) {
    $put_data = file_get_contents("php://input");

    if ($put_data) {
        parse_str($put_data, $put_vars);

        if (isset($put_vars['reportId'])) {
            $reportId = $put_vars['reportId'];

            if (isset($put_vars['action'])) {
                switch ($put_vars['action']) {
                    case 'block':
                        handleBlockContent($conn, $reportId);
                        break;
                    case 'resolve':
                        handleResolveTicket($conn, $reportId);
                        break;
                    default:
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } else {
                echo json_encode(['error' => 'Action not specified']);
            }
        } else {
            echo json_encode(['error' => 'Report ID not specified']);
        }
    } else {
        echo json_encode(['error' => 'PUT data not received']);
    }
}

function handleBlockContent($conn, $reportId) {
    $quotedReportId = $conn->real_escape_string($reportId);
    $conn->query("UPDATE reports SET state = 1 WHERE id = '$quotedReportId'");
    exit('Content blocked successfully');
}

function handleResolveTicket($conn, $reportId) {
    $quotedReportId = $conn->real_escape_string($reportId);
    $conn->query("UPDATE reports SET state = 'CLOSED' WHERE id = '$quotedReportId'");
    exit('Ticket resolved successfully');
}


?>
