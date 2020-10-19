<?php
require_once 'details.php';
function compare_time($time){
    $timeDiff = time() - $time;
    if ($timeDiff < 30) {
        return 'Now';
    }

    if ($timeDiff < 60) {
        return '1 minute ago';
    }

    if ($timeDiff < 3600) {
        $extraTime = round($timeDiff / 60);
        return "$extraTime minutes ago";
    }

    if ($timeDiff < 5400) {
        $extraTime = round($timeDiff / 3600);
        return '1 hour ago';
    }

    if ($timeDiff < 172800) {
        $extraTime = round($timeDiff / 3600);
       return "$extraTime hours ago";
    }

    if ($time === 0) {
        return 'Never';
    }

    $extraTime = round($timeDiff / 86400);
    return "$extraTime days ago";
}
function is_session_started()
{
    if (PHP_SAPI !== 'cli') {
        if (PHP_VERSION_ID >= 50400) {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        return session_id() !== '';
    }
    return FALSE;
}
if (is_session_started() === FALSE) {
    session_start();
}

function detectMobile(){
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}




$headers = array('CLIENT_IP', 'FORWARDED', 'FORWARDED_FOR', 'FORWARDED_FOR_IP', 'VIA', 'X_FORWARDED', 'X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED_FOR_IP', 'HTTP_PROXY_CONNECTION', 'HTTP_VIA', 'HTTP_X_FORWARDED', 'HTTP_X_FORWARDED_FOR');
foreach ($headers as $header) {
    if (isset($_SERVER[$header])) {
        die('Proxy access not allowed.');
    }
}
/*if ((!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) && $_SERVER['REQUEST_URI'] !== '/login.php' && $_SERVER['REQUEST_URI'] !== '/register.php' && $_SERVER['REQUEST_URI'] !== '/merchant.php?merchantName=edward') {
    header('location: login.php');
    exit;
}*/



/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
// Check connection
if ($link === false) {
    die('ERROR: Could not connect. ' . mysqli_connect_error());
}

/*function refreshSeenTime($link)
{
    $sql2 = 'UPDATE users SET last_seen = unix_timestamp() WHERE username = ?';
    if ($stmt2 = mysqli_prepare($link, $sql2)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt2, 's', $username);
        $username = $_SESSION['username'];
        // Attempt to execute the prepared statement
        if (!mysqli_stmt_execute($stmt2)) {
            $error = mysqli_error($link);
            echo "Something went wrong. Please try again later. $error";
        }
        mysqli_stmt_close($stmt2);
    }
}*/
/*
if (isset($_SESSION['username'])) {
    refreshSeenTime($link);
    echo '<script src="https://cdn.jsdelivr.net/npm/socket.io-client@2/dist/socket.io.js"></script>';
}*/
function days_in_month($month, $year)
{
// calculate number of days in a month
    return $month === 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}
function checkSession($variable){
    return isset($_SESSION[$variable]) && $_SESSION[$variable] === true;

}
function checkPermission($permission){
    return isset($_SESSION['permissions'][$permission]) && $_SESSION['permissions'][$permission] === true;

}

function checkAdminOrPermission($permission){
    return checkPermission('admin') || checkPermission($permission);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        try {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        } catch (Exception $e) {
        }
    }
    return $randomString;
}
function sumDays($startDate, $days = 0, $format = 'Y-m-d') {
    $incrementing = $days > 0;
    $days         = abs($days);

    while ($days > 0) {
        $tsDate    = strtotime($startDate . ' ' . ($incrementing ? '+' : '-') . ' 1 days');
        $startDate = date('Y-m-d', $tsDate);

        if (date('N', $tsDate) < 6) {
            $days--;
        }
    }

    return date($format, strtotime($startDate));
}