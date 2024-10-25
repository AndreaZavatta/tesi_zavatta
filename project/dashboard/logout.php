<?php
session_start();
session_destroy(); // Distrugge la sessione e disconnette l'utente
header('Location: ../loginRegistration/login.php');
exit();
?>
