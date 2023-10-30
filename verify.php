
<?php
// Read the JSON data sent from the Roblox game
$requestData = json_decode(file_get_contents("php://input"));

// Check if the gameId field is present in the JSON data
if (isset($requestData->gameId)) {
    // Retrieve the game ID from the JSON data
    $gameId = $requestData->gameId;

    // Establish a database connection (replace with your database credentials)
    $host = "MYSQL SERVER HERE";
    $dbname = "MYSQL DATABASE HERE";
    $username = "MYSQL USERNAME HERE";
    $password = "MYSQLDB PASSWORD HERE";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

        // Prepare a database query to check if the game ID is valid within the date range
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Games WHERE game_id = :gameId AND CURDATE() <= subscribe_end
        ");
        $stmt->bindParam(':gameId', $gameId, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the result (number of matching records)
        $result = $stmt->fetchColumn();

        // Check if the result is greater than zero (game ID is valid within the date range)
        if ($result > 0) {
            $response = array("allowed" => true);
        } else {
            $response = array("allowed" => false);
        }
    } catch (PDOException $e) {
        $response = array("error" => "Database error: " . $e->getMessage());
    }
} else {
    // The gameId field is missing in the JSON data
    $response = array("error" => "Missing gameId field in the request.");
}

// Set the response content type to JSON
header("Content-Type: application/json");

// Send the response as JSON
echo json_encode($response);
?>
