<?php
/**
 * User Logout Page
 * PHP CRUD Blog Application - ApexPlanet Internship
 */

require_once '../includes/auth.php';

// Logout user
logoutUser();

// Redirect to home page
header('Location: ../index.php');
exit();
?>
