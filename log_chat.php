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

// Define your Perspective API key
$perspectiveApiKey = "PERSPECTIVE API KEY HERE";

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the JSON data sent from the Roblox game
    $requestData = json_decode(file_get_contents("php://input"));

    // Check if the required fields are present in the JSON data
    if (isset($requestData->gameId) && isset($requestData->playerName) && isset($requestData->chatMessage)) {
        $gameId = $requestData->gameId;
        $playerName = $requestData->playerName;
        $chatMessage = $requestData->chatMessage;

        $timezone = getTimezoneFromID($gameId);
            
        // Set the default timezone for PHP
        date_default_timezone_set($timezone);

        $currentDateTime = date("Y-m-d H:i:s");

        // Prepare the SQL statement for inserting chat data
        $stmt = $pdo->prepare("INSERT INTO Chatlogs (game_id, Username, Content, time_spoken) VALUES (:gameId, :username, :content, :datetimenow)");

        // Bind parameters
        $stmt->bindParam(':gameId', $gameId);
        $stmt->bindParam(':username', $playerName);
        $stmt->bindParam(':content', $chatMessage);
        $stmt->bindParam(':datetimenow', $currentDateTime);

        // Execute the SQL statement
        $stmt->execute();

        // Send the user's message to the Perspective API using cURL
        $perspectiveApiUrl = "https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=" . $perspectiveApiKey;

        // Data to send to the Perspective API
        $requestData = [
            'comment' => ['text' => $chatMessage],
            'languages' => ['en'],
            'doNotStore' => true, // Specify the language(s) for analysis
            'requestedAttributes' => [
                'TOXICITY' => [
                    'scoreType' => 'PROBABILITY',
                    'scoreThreshold' => 0.7, // Adjust the threshold as needed
                ],
                'SEVERE_TOXICITY' => [
                    'scoreType' => 'PROBABILITY',
                    'scoreThreshold' => 0.7,
                ],
                'IDENTITY_ATTACK' => [
                    'scoreType' => 'PROBABILITY',
                    'scoreThreshold' => 0.7,
                ],
                'INSULT' => [
                    'scoreType' => 'PROBABILITY',
                    'scoreThreshold' => 0.7,
                ],
                'THREAT' => [
                    'scoreType' => 'PROBABILITY',
                    'scoreThreshold' => 0.7,
                ],
            ],
        ];

        // Create cURL handle
        $ch = curl_init($perspectiveApiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

        // Execute the cURL request
        $perspectiveApiResponse = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }

        // Close the cURL handle
        curl_close($ch);

        // Decode the Perspective API response
        $perspectiveApiResult = json_decode($perspectiveApiResponse, true);

        // Extract scores from Perspective API results
        $toxicityScore = $perspectiveApiResult['attributeScores']['TOXICITY']['summaryScore']['value'];
        $severeToxicityScore = $perspectiveApiResult['attributeScores']['SEVERE_TOXICITY']['summaryScore']['value'];
        $identityAttackScore = $perspectiveApiResult['attributeScores']['IDENTITY_ATTACK']['summaryScore']['value'];
        $insultScore = $perspectiveApiResult['attributeScores']['INSULT']['summaryScore']['value'];
        $threatScore = $perspectiveApiResult['attributeScores']['THREAT']['summaryScore']['value'];

        // Prepare the SQL statement for inserting scores into Chatlog_results
        $stmt = $pdo->prepare("INSERT INTO Chatlog_results (chat_id, toxicity, severe_toxicity, identity_attack, insult, threat) VALUES (LAST_INSERT_ID(), :toxicity, :severeToxicity, :identityAttack, :insult, :threat)");

        // Bind parameters
        $stmt->bindParam(':toxicity', $toxicityScore);
        $stmt->bindParam(':severeToxicity', $severeToxicityScore);
        $stmt->bindParam(':identityAttack', $identityAttackScore);
        $stmt->bindParam(':insult', $insultScore);
        $stmt->bindParam(':threat', $threatScore);

        // Execute the SQL statement
        $stmt->execute();

        // Send a success response with the Perspective API results
        $response = [
            "logged" => true,
            "perspectiveResults" => $perspectiveApiResult,
        ];
    } else {
        // Send an error response for missing fields
        $response = array("error" => "Missing required fields.");
    }
} catch (PDOException $e) {
    // Send an error response for database errors
    $response = array("error" => "Database error: " . $e->getMessage());
} catch (Exception $e) {
    // Send an error response for cURL errors
    $response = array("error" => $e->getMessage());
}

// Set the response content type to JSON
header("Content-Type: application/json");

// Send the response as JSON
echo json_encode($response);
?>
