<?php
require "../db_connection.php";
session_start();



// Now select the database
$connection->select_db($dbName);

// Check and create the 'admin' table if it doesn't exist
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL
    );
";
if ($connection->query($createTableQuery) !== TRUE) {
    die("Error creating table: " . $connection->error);
}


// Now you can proceed with the rest of your code
$errorMessage = '';

// Function to register a new user
function register($username, $password, $connection) {
    global $errorMessage;

    // Server-side password constraints
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\\W]/", $password)) {
        $errorMessage = "Password does not meet the security requirements!";
        return;
    }

    // Check if the username already exists
    $checkUserExists = $connection->prepare("SELECT * FROM admin WHERE username = ?");
    $checkUserExists->bind_param('s', $username);
    $checkUserExists->execute();
    $checkUserExists->store_result();

    if ($checkUserExists->num_rows > 0) {
        $errorMessage = "Username is already taken!";
        return;
    }

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user
    $stmt = $connection->prepare("INSERT INTO admin (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $passwordHash);

    if ($stmt->execute()) {
        // Redirect to login after successful registration
        header('Location: login.php?message=success');
        exit();
    } else {
        $errorMessage = "Error registering: " . $connection->error;
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    register($username, $password, $connection);
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
            width: 350px;
            margin: 0 auto;
            background-color: #f9f3e7; /* Light beige for a warm, soft look */
            padding: 50px; /* Add some padding for aesthetics */
            border-radius: 8px; /* Rounded corners for the container */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Subtle shadow effect */
        }
        .error-message {
            color: red;
        }
        body {
            background-color: #faebd7; /* Light brown color */
        }
    </style>
    <script>
        function validatePassword() {
			const password = document.getElementById("password").value;
			const errorMessage = document.getElementById("password-error");

			console.log("Password entered:", password); // Check if the password is being captured

			// Password constraints
			const regexLower = /[a-z]/;
			const regexUpper = /[A-Z]/;
			const regexNumber = /[0-9]/;
			const regexSpecial = /[^a-zA-Z0-9]/;

			if (password.length < 8 || !regexLower.test(password) || !regexUpper.test(password) || !regexNumber.test(password) || !regexSpecial.test(password)) {
				errorMessage.style.display = "block";
				console.log("Password validation failed");
				return false;
			} else {
				errorMessage.style.display = "none";
				console.log("Password validation passed");
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

            <button type="submit">Registrati</button>
        </form>
        <div class="login-link">
            <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
        </div>
    </div>
</body>
</html>
