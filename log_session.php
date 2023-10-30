<?php
function getTimezoneFromID($game_id){
    $host = "MYSQL SERVER HERE";
    $dbname = "MYSQL DATABASE HERE";
    $username = "MYSQL USERNAME HERE";
    $password = "MYSQLDB PASSWORD HERE";

    try{
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT Games.*, Users.timezone 
        FROM Games 
        JOIN Users ON Games.user_id = Users.id 
        WHERE Games.game_id = :game_id");
        $stmt->bindParam(':game_id', $game_id);
        $stmt->execute();

        $results = $stmt->fetch(PDO::FETCH_ASSOC);

        $no_email = "User not found!";

        if($results){
            $username = $results['timezone'];
            return $username;
        }else{
            return $no_email;
        }

    }catch (PDOException $e) {
        return $e; // Return 0 in case of an error
    }

}
// Replace these with your database credentials
$host = "MYSQL SERVER HERE";
$dbname = "MYSQL DATABASE HERE";
$username = "MYSQL USERNAME HERE";
$password = "MYSQLDB PASSWORD HERE";

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the JSON data sent from Roblox
    $requestData = json_decode(file_get_contents("php://input"));

    if ($requestData) {
        // Extract data from JSON
        $playerId = $requestData->playerId;
        $playerName = $requestData->playerName;
        $gameId = $requestData->gameId;
        $event = $requestData->event;
        $placeid = $requestData->placeid;

        if ($event === "join") {
            // Check if there is an existing record for the player in this game
            $stmt = $pdo->prepare("SELECT * FROM Join_logs WHERE game_id = :gameId AND Username = :playerName AND Leave_time IS NULL");
            $stmt->bindParam(':gameId', $gameId);
            $stmt->bindParam(':playerName', $playerName);
            $stmt->execute();
            
            $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // Player joined again, update the existing record with Leave_time = null
                $stmt = $pdo->prepare("UPDATE Join_logs SET Leave_time = NULL WHERE id = :id");
                $stmt->bindParam(':id', $existingRecord['id']);
                $stmt->execute();
            } else {
                // Player joined for the first time, create a new record
            
                // Get the timezone for the game
                $timezone = getTimezoneFromID($gameId);
            
                // Set the default timezone for PHP
                date_default_timezone_set($timezone);
            
                $joinTime = date("Y-m-d H:i:s");
                $leaveTime = null; // Player just joined, so leave time is null
                
                $stmt = $pdo->prepare("INSERT INTO Join_logs (game_id, Username, Join_time, Leave_time, place_id) VALUES (:gameId, :playerName, :joinTime, :leaveTime, :place_id)");
                $stmt->bindParam(':gameId', $gameId);
                $stmt->bindParam(':playerName', $playerName);
                $stmt->bindParam(':joinTime', $joinTime);
                $stmt->bindParam(':leaveTime', $leaveTime);
                $stmt->bindParam(":place_id", $placeid);
                $stmt->execute();
            }
        } elseif ($event === "leave") {
            $timezone = getTimezoneFromID($gameId);
            
            // Set the default timezone for PHP
            date_default_timezone_set($timezone);
            // Player left the game, set the Leave_time
            $leaveTime = date("Y-m-d H:i:s");
            
            // Update the existing record with the leave time
            $stmt = $pdo->prepare("UPDATE Join_logs SET Leave_time = :leaveTime WHERE game_id = :gameId AND Username = :playerName AND Leave_time IS NULL");
            $stmt->bindParam(':leaveTime', $leaveTime);
            $stmt->bindParam(':gameId', $gameId);
            $stmt->bindParam(':playerName', $playerName);
            $stmt->execute();

            // Calculate game time and insert it into the Activity_log table
            $stmt = $pdo->prepare("INSERT INTO Activity_log (game_id, Username, game_time)
                       SELECT j.game_id, j.Username, TIMEDIFF(j.Leave_time, j.Join_time)
                       FROM Join_logs j
                       WHERE j.game_id = :gameId AND j.Username = :playerName");
            $stmt->bindParam(':gameId', $gameId);
            $stmt->bindParam(':playerName', $playerName);
            $stmt->execute();

        } else {
            // Invalid event type
            $response = array("error" => "Invalid event type.");
            echo json_encode($response);
            exit();
        }

        // Send a success response
        $response = array("success" => true);
    } else {
        // Send an error response for invalid JSON data
        $response = array("error" => "Invalid JSON data.");
    }
} catch (PDOException $e) {
    // Send an error response for database errors
    $response = array("error" => "Database error: " . $e->getMessage());
}

// Set the response content type to JSON
header("Content-Type: application/json");

// Send the response as JSON
echo json_encode($response);
?>