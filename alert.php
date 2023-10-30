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

// Create a PDO database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Receive JSON data from the POST request
$jsonData = file_get_contents('php://input');

// Decode the JSON data
$data = json_decode($jsonData, true);

if ($data) {
    // Extract the data from the JSON
    $gameId = $data['gameid'];
    $username = $data['player'];
    $timezone = getTimezoneFromID($gameId);
            
    // Set the default timezone for PHP
    date_default_timezone_set($timezone);
    $datetime = date('Y-m-d H:i:s');
    $placeid = $data['placeid'];

    // Insert the data into the Mod_requests table
    $sql = "INSERT INTO Mod_requests (game_id, Username, Time_requested, placeid) VALUES (:gameId, :username, :timeRequested, :place)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":gameId", $gameId);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":timeRequested", $datetime);
    $stmt->bindParam(":place", $placeid);

    try {
        $stmt->execute();
        echo "Moderation request recorded successfully.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid JSON data.";
}
?>
