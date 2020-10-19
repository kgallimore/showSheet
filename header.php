<?php
require_once 'config/config.php';
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="resources/css/header.css">
        <link rel="stylesheet" href="resources/css/bootstrap.css">

    <script src="resources/js/jquery-3.5.1.min.js"></script>';
echo "<div class='page-header'>";
if ($_SERVER['REQUEST_URI'] !== '/index.php' && $_SERVER['REQUEST_URI'] !== '/' && $_SERVER['REQUEST_URI'] !== '') {
    echo '<a href="/" class="btn btn-success">Home</a>';
}
if($_SERVER['REQUEST_URI'] !== '/admin.php' && checkAdminOrPermission('manageUsers')) {
    echo '<a href="admin.php" class="btn btn-primary ">Admin Page</a>';
}
if($_SERVER['REQUEST_URI'] !== '/manageCrew.php' && checkAdminOrPermission('manageCrew')) {
    echo '<a href="manageCrew.php" class="btn btn-primary ">Manage Crew</a>';
}



/*if ($_SERVER['REQUEST_URI'] !== '/requests.php') {
    $sql = 'SELECT * FROM service_requests WHERE customer = ? AND last_seen_cust < last_update';

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, 's', $email);
        $email = $_SESSION['email'];
        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Store result
            mysqli_stmt_store_result($stmt);

            // Check if email exists, if yes then verify password
            $number_requests = mysqli_stmt_num_rows($stmt);
            if ($number_requests > 0) {
                echo "<a href=\"requests.php\" class=\"btn btn-warning \">Request updates: $number_requests</a>";
            } else {
                echo "<a href=\"requests.php\" class=\"btn btn-primary \">Request updates: $number_requests</a>";
            }

        }
    }
}*/
if ((!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true)) {
    echo '<a href="register.php" class="btn btn-warning">Register an account</a><a href="login.php" class="btn btn-danger">Login</a>';
} else {
    echo '<a href="reset-password.php" class="btn btn-warning">Reset Password</a><a href="logout.php" class="btn btn-danger">Sign Out</a>';
}

if(isset($_SESSION['available_permissions']) && !empty($_SESSION['available_permissions'])){
    echo "<span style='align-self: center'>Change Company: ";
    echo '<select disabled name="changeCompany" id="changeCompany">';
    foreach ($_SESSION['available_permissions'] as $companyid =>$information){
        $name = $information[1];
        echo "<option value='$companyid'>$name</option>";

    }
    echo '</select></span>';
}
echo '<h1 style="display: inline; right: 0; position: absolute; margin: 0; padding: 0">Hello, <b>';
if ((!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true)) {
    echo 'Guest';
} else {
    echo htmlspecialchars($_SESSION['email']);
}
echo '</b>.</h1></div>';
