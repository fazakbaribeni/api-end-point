<?php

// Set headers to allow cross-origin resource sharing (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "api_database";

// Function to handle POST requests
class API_Handler
{
    private $dbh;

    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePOSTRequest();
        }else{
            $this->handleGETRequest($_GET['id']);
        }
    }

    private function connect()
    {
        global $servername, $username, $password, $dbname;

        try {
            $this->dbh = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["message" => "Database connection failed: " . $e->getMessage()]);
            exit();
        }
    }

    /**
     * @return void
     */
    public function handlePOSTRequest()
    {
        // Get the data from the request
        if ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        } else {
            // If the content type is JSON
            $data = json_decode(file_get_contents("file.json"));
            $name = filter_var($data->name, FILTER_SANITIZE_STRING);
            $email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
        }


        if ($name && $email) {
            // Create a database connection
            $this->connect();

            try {
                // Insert data into the table using prepared statements
                $sql = "INSERT INTO users (name, email) VALUES (?, ?)";
                $stmt = $this->dbh->prepare($sql);
                $stmt->execute([$name, $email]);

                http_response_code(201); // Created
                echo json_encode(["message" => "Data posted successfully"]);
            } catch (PDOException $e) {
                http_response_code(500); // Internal Server Error
                echo json_encode(["message" => "Error posting data: " . $e->getMessage()]);
            }

            // Close the database connection
            $this->dbh = null;
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(["message" => "Invalid data"]);
        }
    }


    private function handleGETRequest($id){

        // Create a database connection
        $this->connect();

        try {
            // Fetch data from the table
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute([$id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            // Return the data
            if ($data) {
                http_response_code(200); // OK
                echo json_encode($data);
            }



        }catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["message" => "Error fetching data: " . $e->getMessage()]);
        }

        // Close the database connection
        $this->dbh = null;

        // Return the data
    }


}

// Main API entry point
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $api_handler = new API_Handler();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Method Not Allowed"]);
}
