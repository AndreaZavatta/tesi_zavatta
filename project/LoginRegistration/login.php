<?php
require "../db_connection.php";
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    login($username, $password, $connection);
}

// Function to verify login credentials
function login($username, $password, $connection) {
    global $errorMessage;

    // Prepare and execute the query
    $stmt = $connection->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Set session for the authenticated admin
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: ../dashboard/dashboard.php');
        exit();
    } else {
        $errorMessage = "Username o password errati!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- Collegamento al file CSS esterno -->
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login Admin</h2>
        
        <!-- Mostra messaggio di successo se viene dalla registrazione -->
        <?php if (isset($_GET['message']) && $_GET['message'] === 'success') : ?>
            <div class="success-message">Registrazione completata! Ora puoi accedere.</div>
        <?php endif; ?>

        <!-- Mostra messaggio di errore se presente -->
        <?php if (!empty($errorMessage)) : ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <!-- Link per andare alla registrazione -->
        <div class="login-link">
            <p>Non hai un account? <a href="register.php">Registrati qui</a></p>
        </div>
        <div class="login-link">
            <p>Non vuoi registrarti? <a href="../dashboard/dashboard.php">Accedi senza login</a></p>
        </div>
    </div>
</body>
</html>
