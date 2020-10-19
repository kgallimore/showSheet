<?php
require_once 'header.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['responseid']) && strlen($_GET['responseid']) === 11) {
    $response_id = $_GET['responseid'];
    $response_lookup_sql = 'SELECT state, show_name, show_start_date, rehearsals_start_date, rehearsal_days, show_days, show_time_start, show_time_end, company_name  FROM crewbooking JOIN shows s on crewbooking.showid = s.showid JOIN settings s2 on crewbooking.companyid = s2.companyid WHERE responseidself = ?';
    if ($response_lookup_stmt = mysqli_prepare($link, $response_lookup_sql)) {
        mysqli_stmt_bind_param($response_lookup_stmt, 's', $_GET['responseid']);
        if (mysqli_stmt_execute($response_lookup_stmt)) {
            mysqli_stmt_bind_result($response_lookup_stmt, $response_state, $show_name, $show_start_date, $rehearsals_start_date, $rehearsal_days, $show_days, $show_time_start, $show_time_end, $company_name);
            if (mysqli_stmt_fetch($response_lookup_stmt)) {
                if ($response_state === 'Requested') {
                    echo "<title>Confirm Booking</title></head><body>
<table style='margin: auto; font-size: x-large' class='table-striped'>
<thead><tr><th colspan='2'>Please confirm or reject the request below:</th></tr></thead>
<tbody>
<tr>
<td>
Company:
</td>
<td>
$company_name
</td>
</tr>
<tr>
<td>
Show Name:
</td>
<td>
$show_name
</td>
</tr>
<tr>
<td>
Rehearsal Days:
</td>
<td>
$rehearsal_days (Starting on <strong>$rehearsals_start_date</strong>)
</td>
</tr>
<tr>
<td>
Show Days:
</td>
<td>
$show_days (Starting on <strong>$show_start_date</strong>)
</td>
</tr>
<tr>
<td>
Starting Time:
</td>
<td>
$show_time_start
</td>
</tr>
<tr>
<td>
Ending Time:
</td>
<td>
$show_time_end
</td>
</tr>
<tr style='text-align: center'>
<td>
<form action='bookingConfirm.php' method='post'><input value='$response_id' type='hidden' name='responseid'><input value=0 type='hidden' name='response'><button class='btn btn-danger' type='submit'>Reject Booking</button></form>
</td>
<td>
<form action='bookingConfirm.php' method='post'><input value='$response_id' type='hidden' name='responseid'><input value=1 type='hidden' name='response'><button class='btn btn-success' type='submit'>Confirm Booking</button></form>
</td>
</tr>
</tbody>
</table>
</body>";
                } elseif (empty($response_state)) {
                    echo "Couldn't find what you were looking for. Please try again later, otherwise notify the site administrator";
                } else {
                    echo "Your booking has already been set. Current status: <strong>$response_state</strong>";
                }

            }
        }
        mysqli_stmt_close($response_lookup_stmt);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responseid'], $_POST['response']) && !empty($_POST['responseid']) && !empty($_POST['response'])) {
    $response_id = $_POST['responseid'];
    $response_num = $_POST['response'];
    if (strlen($response_id) === 11) {
        $response = 'Self ';
        $sql = "UPDATE crewbooking SET state = ?, responseidself = '' WHERE responseidself = ?";
    } else {
        $response = 'Manually ';
        $sql = "UPDATE crewbooking SET state = ?, confirmedby = ? WHERE responseidmanual = ?";
    }
    if ($response_num === '0') {
        $response .= 'Rejected';
    } elseif ($response_num === '1') {
        $response .= 'Confirmed';
    } else {
        die('Invalid response: ' . $response_num);
    }
    if ($stmt = mysqli_prepare($link, $sql)) {
        if (strlen($response_id) === 11) {
            mysqli_stmt_bind_param($stmt, 'ss', $response, $response_id);
        } else {
            mysqli_stmt_bind_param($stmt, 'sis', $response, $_SESSION['uid'], $response_id);
        }
        if (mysqli_stmt_execute($stmt)) {
            if (strlen($response_id) === 10) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            }
            echo "Your response has been submitted.";

        } else {
            echo "Error updating response.";
        }
        mysqli_stmt_close($stmt);

    }

} else {
    echo "<h1>Hey, what are you doing here? Are you lost?</h1>";
}
mysqli_close($link);
