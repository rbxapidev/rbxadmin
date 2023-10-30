<?php
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the game_id sent from Roblox
    $gameId = $_GET['game_id'];

    // Perform a query to fetch the banlist for the given game_id from the database
    $host = "MYSQL SERVER HERE";
    $dbname = "MYSQL DATABASE HERE";
    $username = "MYSQL USERNAME HERE";
    $password = "MYSQLDB PASSWORD HERE";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch the banlist for the specified game_id
        $stmt = $pdo->prepare("SELECT Username FROM Banlist WHERE game_id = :gameId");
        $stmt->bindParam(':gameId', $gameId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the results into an array
        $banlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the banlist as a JSON response
        echo json_encode($banlist);
    } catch (PDOException $e) {
        // Handle database connection errors
        echo "Database Error: " . $e->getMessage();
    }
} else {
    // Handle non-POST requests
    echo "Invalid request method.";
}
?>