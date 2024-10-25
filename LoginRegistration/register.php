<?php
session_start();

// Check if the connection details are posted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set the session variables for the database connection based on user input
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['port'] = $_POST['port'];
    $_SESSION['db_username'] = $_POST['db_username'];
    $_SESSION['db_password'] = $_POST['db_password'];

    // Attempt to establish the database connection
    try {
        echo($_SESSION['host'].$_SESSION['db_username'].$_SESSION['db_password'].''.$_SESSION['port']);
        require "../db_connection.php";
        // Check for connection errors
        if ($connection->connect_error) {
            throw new Exception("Connessione al database fallita. Verifica le tue credenziali.");
        }
    } catch (Exception $e) {
        $errorMessage = "connessione al database non riuscita, assicurati che tutti i dati siano corretti!"; // Capture the error message
    }

    // Proceed only if the connection was successful
    if (!isset($errorMessage)) {
        // Now select the database
        $connection->select_db($dbName);

        // Check and create the 'admin' table if it doesn't exist
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                host VARCHAR(255) NOT NULL,
                port INT NOT NULL,
                db_username VARCHAR(50) NOT NULL,
                db_password VARCHAR(255) NOT NULL
            );
        ";
        if ($connection->query($createTableQuery) !== TRUE) {
            die("Error creating table: " . $connection->error);
        }

        $username = $_POST['username'];
        $password = $_POST['password'];

        $db_password_hashed = password_hash($_SESSION['db_password'], PASSWORD_DEFAULT);
        // Function to register a new user
        function register($username, $password, $db_password_hashed, $connection) {
            global $errorMessage;

            // Server-side password constraints
            if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\\W]/", $password)) {
                $errorMessage = "La password non soddisfa i requisiti di sicurezza!";
                return;
            }

            // Check if the username already exists
            $checkUserExists = $connection->prepare("SELECT * FROM admin WHERE username = ?");
            $checkUserExists->bind_param('s', $username);
            $checkUserExists->execute();
            $checkUserExists->store_result();

            if ($checkUserExists->num_rows > 0) {
                $errorMessage = "L'username è già stato preso!";
                return;
            }

            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user along with database connection details
            $stmt = $connection->prepare("INSERT INTO admin (username, password_hash, host, port, db_username, db_password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $username, $passwordHash, $_SESSION['host'], $_SESSION['port'], $_SESSION['db_username'], $db_password_hashed);

            if ($stmt->execute()) {
                // Redirect to login after successful registration
                header('Location: login.php?message=success');
                exit();
            } else {
                $errorMessage = "Errore durante la registrazione: " . $connection->error;
            }

            $stmt->close();
        }

        register($username, $password, $db_password_hashed, $connection);
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .form-container {
            width: 300px;
            margin: 0 auto;
        }
        .error-message {
            color: red;
        }
    </style>
    <script>
        function validatePassword() {
            const password = document.getElementById("password").value;
            const errorMessage = document.getElementById("password-error");

            // Password constraints
            const regexLower = /[a-z]/;
            const regexUpper = /[A-Z]/;
            const regexNumber = /[0-9]/;
            const regexSpecial = /[^a-zA-Z0-9]/;

            if (password.length < 8 || !regexLower.test(password) || !regexUpper.test(password) || !regexNumber.test(password) || !regexSpecial.test(password)) {
                errorMessage.style.display = "block";
                return false;
            } else {
                errorMessage.style.display = "none";
                return true;
            }
        }

        function validateForm() {
            return validatePassword();
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h2>Registrazione Nuovo Utente</h2>
        
        <!-- Messaggio di errore se presente -->
        <?php if (!empty($errorMessage)) : ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" onsubmit="return validateForm()">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" oninput="validatePassword()" required>
            <div id="password-error" class="error-message" style="display:none;">
                La password deve contenere almeno 8 caratteri, includere una lettera maiuscola, una minuscola, un numero e un carattere speciale.
            </div>

            <label for="host">Database Host:</label>
            <input type="text" id="host" name="host" value="localhost" required>

            <label for="port">Database Port:</label>
            <input type="number" id="port" name="port" value="3306" required>

            <label for="db_username">Database Username:</label>
            <input type="text" id="db_username" name="db_username" required>

            <label for="db_password">Database Password:</label>
            <input type="password" id="db_password" name="db_password" required>

            <button type="submit">Registrati</button>
        </form>
        <div class="login-link">
            <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
        </div>
    </div>
</body>
</html>
