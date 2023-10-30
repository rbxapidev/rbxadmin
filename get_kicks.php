<?php
// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the game_id sent from Roblox
    $gameId = $_GET['game_id'];

    // Perform a query to fetch the usernames from the Kicklist table for the given game_id with kick_success = 0
    $host = "MYSQL SERVER HERE";
    $dbname = "MYSQL DATABASE HERE";
    $username = "MYSQL USERNAME HERE";
    $password = "MYSQLDB PASSWORD HERE";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch usernames
        $stmt = $pdo->prepare("SELECT Username FROM Kicklist WHERE game_id = :gameId AND kick_success = '0'");
        $stmt->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the results into an array
        $usernames = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the usernames as a JSON response
        echo json_encode($usernames);

        // Sleep for 10 seconds
        sleep(10);

        // Execute the update query to set kick_success = 1 for the retrieved usernames
        foreach ($usernames as $username) {
            $updateStmt = $pdo->prepare("UPDATE Kicklist SET kick_success = '1' WHERE Username = :username AND game_id = :gameId");
            $updateStmt->bindParam(':username', $username['Username'], PDO::PARAM_STR);
            $updateStmt->bindParam(':gameId', $gameId, PDO::PARAM_INT);
            $updateStmt->execute();
        }

        // Additional actions after the update can be placed here
        // ...

    } catch (PDOException $e) {
        // Handle database connection errors
        echo "Database Error: " . $e->getMessage();
    }
} else {
    // Handle non-GET requests
    echo "Invalid request method.";
}
?>
