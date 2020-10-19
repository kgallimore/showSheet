<?php
include_once 'config/config.php';
if (($_SERVER['REQUEST_METHOD'] === 'POST') && $_SESSION['permissions']['admin'] && isset($_POST['crewid'])) {
    $sql = 'DELETE FROM crew WHERE crewid = ? and companyid = ?';
    $crewid = trim($_POST['crewid']);
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, 'is', $crewid, $_SESSION['company']);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            //store result
            echo 'Success!';
        }

    } else {
        echo 'Error preparing statement';
    }

    // Close statement
    mysqli_stmt_close($stmt);
}
