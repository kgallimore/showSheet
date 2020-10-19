<?php

require_once 'header.php';
require_once 'config/mailer.php';
if (!checkSession('loggedin')) {
    header('location: login.php');
}
if (!checkAdminOrPermission('canBook')) {
    die('Missing required permissions. Please contact your company admin');
}
$month = $_GET['month'];
$year = $_GET['year'];
$day = $_GET['day'];
$formatted_date = date('Ymd', mktime(0, 0, 0, $month, $day, $year));
$sql = 'SELECT studios, resources FROM settings WHERE companyid = ?';
if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['company']);
    if (mysqli_stmt_execute($stmt)) {
        //store result
        mysqli_stmt_bind_result($stmt, $studios, $resources);
        mysqli_stmt_fetch($stmt);
        $studios = json_decode($studios, true);
        $resources = json_decode($resources, true);

    }
}
mysqli_stmt_close($stmt);
if (($_SERVER['REQUEST_METHOD'] === 'POST')) {
    if (isset($_POST['showName'], $_POST['clientName'], $_POST['studio'], $_POST['showStartDate']) && !empty(trim($_POST['clientName']) && !empty(trim($_POST['studio'])) && !empty(trim($_POST['showStartDate'])) && !empty(trim($_POST['showName'])))) {
        $studio = $_POST['studio'];
        $crew = array();
        $client_name_err = $show_name_err = $show_start_date_err = $contact_info_err = $client_phone_err = '';

        if (empty(trim($_POST['showName']))) {
            $show_name_err = 'No show name';
        } else {
            $show_name = trim($_POST['showName']);
        }
        if (empty(trim($_POST['showStartDate']))) {
            $show_start_date_err = 'No start date';
        } else {
            $show_start_date = trim($_POST['showStartDate']);
        }
        if (isset($_POST['showTimeStart']) && !empty(trim($_POST['showTimeStart']))) {
            $show_time_start = trim($_POST['showTimeStart']);
        }
        if (isset($_POST['showTimeEnd']) && !empty(trim($_POST['showTimeEnd']))) {
            $show_time_end = trim($_POST['showTimeEnd']);
        }
        if (empty(trim($_POST['showDays']))) {
            $show_days = 1;
        } else {
            $show_days = trim($_POST['showDays']);
        }
        $shows_end_date = sumDays($show_start_date, $show_days - 1);
        if (empty(trim($_POST['rehearsalDays']))) {
            $rehearsal_days = 0;
        } else {
            $rehearsal_days = trim($_POST['rehearsalDays']);
        }
        $rehearsals_start_date = sumDays($show_start_date, -$rehearsal_days);
        $contact_info = array();
        if (empty(trim($_POST['clientName']))) {
            $client_name_err = 'No client name';
        } else {
            $client_name = trim($_POST['clientName']);
            $contact_info['name'] = $client_name;
        }
        if (!empty(trim($_POST['clientStreet']))) {
            $client_street = trim($_POST['clientStreet']);
            $contact_info['street'] = $client_street;
        }
        if (!empty(trim($_POST['clientAddress']))) {
            $client_address = trim($_POST['clientAddress']);
            $contact_info['address'] = $client_address;
        }
        if (isset($_POST['clientPhone'])) {
            $_POST['clientPhone'] = str_replace(' ', '', $_POST['clientPhone']);
            if (!empty(trim($_POST['clientPhone'])) && strlen(trim($_POST['clientPhone'])) < 10) {
                $client_phone_err = 'Phone seems to be too short';
                $client_phone = trim($_POST['clientPhone']);
            } elseif (preg_match('([\d]{10}|[\d()-]{11,14})', $_POST['clientPhone'])) {
                $client_phone = $_POST['clientPhone'];
                $contact_info['phone'] = trim($client_phone);
            } elseif (strlen(trim($_POST['clientPhone'])) > 10) {
                $client_phone_err = 'Unknown phone format';
            }
        }
        if (!empty(trim($_POST['clientEmail']))) {
            $client_email = trim($_POST['clientEmail']);
            $contact_info['email'] = $client_email;
        }
        try {
            $contact_info = json_encode($contact_info, JSON_THROW_ON_ERROR, 512);
        } catch (Exception $e) {
            $contact_info_err = 'Error encoding contact info: ' . $e;
            echo $contact_info_err;
        }
        if (!empty(trim($_POST['otherNotes']))) {
            $other_notes = $_POST['otherNotes'];
        }
        if (!empty(trim($_POST['numRemotes']))) {
            $num_remotes = $_POST['numRemotes'];
        }
        foreach ($_POST as $post_key => $post_value) {
            if ($post_value && strpos($post_key, 'crew') !== false) {
                $crew_num = substr($post_key, 4);
                $crew[] = $crew_num;
            }

        }
        $crew_encoded = json_encode($crew);
        if (empty($contact_info_err) && empty($show_start_date_err) && empty($show_name_err) && empty($client_name_err) && empty($client_phone_err)) {
            $sql = 'INSERT INTO shows (studio, show_name, show_start_date, show_time_start, show_time_end, show_days, shows_end_date, rehearsal_days, rehearsals_start_date, crew, contact_info, other_notes, num_remotes, other_resources, companyid) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'sssssisissssssi', $studio, $show_name, $show_start_date, $show_time_start, $show_time_end, $show_days, $shows_end_date, $rehearsal_days, $rehearsals_start_date, $crew_encoded, $contact_info, $other_notes, $num_remotes, $other_resources, $_SESSION['company']);
                if (mysqli_stmt_execute($stmt)) {
                    $inserted_id = mysqli_insert_id($link);
                    if (isset($crew) && !empty($crew)) {
                        foreach ($crew as $crew_member_id) {
                            $crewsql = 'INSERT INTO crewbooking (showid, companyid, crewid, responseidself, responseidmanual) VALUES (?,?,?,?,?)';
                            if ($crewstmt = mysqli_prepare($link, $crewsql)) {
                                $response_id_crew = generateRandomString(11);
                                $response_id_manual = generateRandomString();
                                mysqli_stmt_bind_param($crewstmt, 'iiiss', $inserted_id, $_SESSION['company'], $crew_member_id, $response_id_crew, $response_id_manual);
                                if (mysqli_stmt_execute($crewstmt)) {
                                    $crew_info_sql = 'SELECT email, lastName, firstName FROM crew WHERE crewid = ?';
                                    if ($crew_info_stmt = mysqli_prepare($link, $crew_info_sql)) {
                                        mysqli_stmt_bind_param($crew_info_stmt, 'i', $crew_member_id);
                                        if (mysqli_stmt_execute($crew_info_stmt)) {
                                            mysqli_stmt_store_result($crew_info_stmt);
                                            mysqli_stmt_bind_result($crew_info_stmt, $crew_email, $crew_first_name, $crew_last_name);
                                            if (mysqli_stmt_fetch($crew_info_stmt)) {
                                                if(!empty($crew_email)){
                                                    $accept_link = 'https://' . $_SERVER['HTTP_HOST'] . '/bookingConfirm.php?responseid=' . $response_id_crew;
                                                    $img_src = 'https://' . $_SERVER['HTTP_HOST'] . '/resources/images/logo.png';
                                                    $email_body = "<div style='text-align: center; margin: auto; width: 80%; background: whitesmoke; height: 90%'> <img src='https://sheet.gallimo.com/resources/images/logo.png' style='width: 30%'><br>Hello. You have been requested for the show: <strong>$show_name</strong>, for the following date: <strong>$day/$month/$year.</strong>";
                                                    if($rehearsal_days > 0){
                                                        $email_body .= "<br>There are $rehearsal_days rehearsal days starting on <strong>$rehearsals_start_date</strong>.";
                                                    }
                                                    if($show_days > 1){
                                                        $email_body .="<br>There are $show_days ending on <strong>$shows_end_date</strong>.";
                                                    }
                                                    if(!empty($show_time_start) && !empty($show_time_end)){
                                                        $email_body .= "<br>The show will be from <strong>$show_time_start</strong> to <strong>$show_time_end.</strong>";
                                                    }
                                                    $email_body.= "<br><a href='$accept_link'>Please click the here to accept or reject.</a></div>";
                                                    $email_alt_body = "Hello. You have been requested for the show: $show_name, for the following date: $day/$month/$year. Please visit the following link to accept or reject: $accept_link";
                                                    if($sent_mail = (sendOutsideMail($crew_email, "Requested appearance for $day/$month/$year, $show_name", $email_body, $email_alt_body, $crew_first_name . ' ' . $crew_last_name) !== true)){
                                                        die($sent_mail);
                                                    }
                                                }
                                            }
                                        }
                                        mysqli_stmt_close($crew_info_stmt);
                                    }

                                    header('Location: ' . $_SERVER['REQUEST_URI']);

                                }
                            }
                            mysqli_stmt_close($crewstmt);
                        }
                    } else {
                        mysqli_stmt_close($stmt);
                        header('Location: ' . $_SERVER['REQUEST_URI']);

                    }

                } else {
                    echo mysqli_error($link);
                }
            } else {
                echo mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo 'Error in data fields. ' . $client_name_err . $show_name_err . $show_start_date_err . $contact_info_err . $client_phone_err;
        }

    } else {
        echo 'Missing key form components';
    }

}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style>
        html, body {
            width: 100%;
            height: 100%;
        }

        table {
            width: 100%;
            height: 100%;
        }

        th {
            text-align: center;
            font-size: xx-large;
        }
    </style>
</head>
<body>
<table border="1">
    <tbody>
    <tr>

        <?php
        $build_table_header = '';
        $studio_count = count($studios);
        $build_table_header .= "<th colspan='$studio_count'> Bookings For:$day/$month/$year</th></tr><tr>";
        $column_width = 100 / $studio_count;
        foreach ($studios as $studio) {
            $build_table_header .= "<th style='width: $column_width%'>$studio</th>";
        }
        $build_table_header .= '</tr><tr>';
        echo $build_table_header;
        $available_remotes = $resources;
        foreach ($studios as $studio) {


            $sql = 'SELECT showid, show_name, show_time_start, show_time_end, show_days, rehearsal_days, crew, contact_info, other_notes, num_remotes, other_resources, show_start_date FROM shows WHERE companyid = ? AND studio = ? AND rehearsals_start_date <= ? AND ? <= shows_end_date ';
            if ($stmt = mysqli_prepare($link, $sql)) {

                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, 'ssss', $_SESSION['company'], $studio, $formatted_date, $formatted_date);
                if (mysqli_stmt_execute($stmt)) {
                    //store result
                    mysqli_stmt_bind_result($stmt, $show_id, $show_name, $show_time_start, $show_time_end, $show_days, $rehearsal_days, $show_crew, $contact_info, $other_notes, $num_remotes, $other_resources, $show_start_date);

                    if (mysqli_stmt_fetch($stmt)) {
                        $sql_lookup_dict[$studio]['show_id'] = $show_id;
                        $sql_lookup_dict[$studio]['show_name'] = $show_name;
                        $sql_lookup_dict[$studio]['show_time_start'] = $show_time_start;
                        $sql_lookup_dict[$studio]['show_time_end'] = $show_time_end;
                        $sql_lookup_dict[$studio]['show_days'] = $show_days;
                        $sql_lookup_dict[$studio]['rehearsal_days'] = $rehearsal_days;
                        $sql_lookup_dict[$studio]['show_crew'] = $show_crew;
                        $sql_lookup_dict[$studio]['contact_info'] = $contact_info;
                        $sql_lookup_dict[$studio]['other_notes'] = $other_notes;
                        $sql_lookup_dict[$studio]['num_remotes'] = $num_remotes;
                        $sql_lookup_dict[$studio]['other_resources'] = $other_resources;
                        $sql_lookup_dict[$studio]['show_start_date'] = $show_start_date;
                        $available_remotes -= $num_remotes;


                    } else {
                        $sql_lookup_dict[$studio] = false;
                    }
                    mysqli_stmt_close($stmt);


                }
            } else {
                echo 'Error preparing statement:' . mysqli_error($link);
            }


        }
        if (isset($sql_lookup_dict)) {
            foreach ($sql_lookup_dict as $studio => $values) {
                echo "<td style='height: 90%; position: relative'>";
                if ($values) {
                    $show_name = '<strong>' . $values['show_name'] . '</strong>';
                    if (strtotime($formatted_date) - strtotime($values['show_start_date']) >= 0) {
                        $show_name .= ' (Show)';
                    } else {
                        $show_name .= ' (Rehearsal)';
                    }
                    $contact_info = json_decode($values['contact_info'], true);
                    $client_name = $contact_info['name'];
                    $client_email = $contact_info['email'] ?? '';
                    $client_phone = $contact_info['phone'] ?? '';
                    $client_address = $contact_info['address'] ?? '';
                    $client_street = $contact_info['street'] ?? '';
                    $show_crew = json_decode($values['show_crew'], true);
                    echo "<table class='table-striped' style='height: min-content; width: fit-content; position: absolute; top: 0; font-size: x-large; margin: auto'>
                    <tbody>
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
                            Client Name:
                        </td>
                        <td>
                            $client_name
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Street:
                        </td>
                        <td>
                            $client_street
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client City, State, Zip:
                        </td>
                        <td>
                           $client_address
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Phone:
                        </td>
                        <td>
                            $client_phone
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Email:
                        </td>
                        <td>
                            $client_email
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Start Time:
                        </td>
                        <td>
                            " . $values['show_time_start'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            End Time:
                        </td>
                        <td>
                        ' . $values['show_time_end'] . '
                            
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Number of Show Days:
                        </td>
                        <td>
                            ' . $values['show_days'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Rehearsal Days:
                        </td>
                        <td>
                            ' . $values['rehearsal_days'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Number of Remotes:
                        </td>
                        <td>
                            ' . $values['num_remotes'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Teleprompter:
                        </td>
                        <td>
                            ' . $values['other_resources'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Other Notes:
                        </td>
                        <td>
                            ' . $values['other_notes'] . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Crew:
                        </td>
                        <td>';
                    if (isset($show_crew) && !empty($show_crew)) {
                        $recent_category = '';
                        foreach ($show_crew as $crew) {
                            $sql2 = 'SELECT firstName, lastName, crewCategory, responseidmanual, state FROM crew JOIN crewbooking c ON c.crewid = crew.crewid WHERE c.crewid = ? AND c.companyid = ? AND showid = ?';
                            if ($stmt2 = mysqli_prepare($link, $sql2)) {
                                mysqli_stmt_bind_param($stmt2, 'iii', $crew, $_SESSION['company'], $values['show_id']);
                                if (mysqli_stmt_execute($stmt2)) {
                                    mysqli_stmt_bind_result($stmt2, $crew_first_name, $crew_last_name, $crew_category, $response_id, $state);
                                    mysqli_stmt_fetch($stmt2);
                                    if ($recent_category !== $crew_category) {
                                        $recent_category = $crew_category;
                                        echo "<h3>$crew_category:</h3>";
                                    }
                                    echo '<h5>' . $crew_first_name . ' ' . $crew_last_name . ': ' . "<strong>$state</strong></h5><div style='display: flex'>
<br><form action='bookingConfirm.php' method='post'><input value='$response_id' type='hidden' name='responseid'><input value=0 type='hidden' name='response'><button class='btn btn-danger' type='submit'>Reject Booking</button></form>
<form action='bookingConfirm.php' method='post'><input value='$response_id' type='hidden' name='responseid'><input value=1 type='hidden' name='response'><button class='btn btn-success' type='submit'>Confirm Booking</button></form></div>
";
                                }
                                mysqli_stmt_close($stmt2);
                            }
                            //echo "$crew<br>";
                        }
                    }

                    echo '</td>
                    </tr></tbody>
                </table>
                        ';
                } else {


                    $to_echo = "
        
        <form style='top: 0; left: 0; position: absolute' action='" . htmlspecialchars($_SERVER['REQUEST_URI']) . "' method='post'>
    
                <input type='hidden' name='showStartDate' value='$formatted_date'>
                <input type='hidden' name='studio' value='$studio'>
                <table class='table-striped' style='margin: auto'>
                    <tbody>
                    <tr>
                        <td>
                            Show Name:
                        </td>
                        <td>
                            <input name='showName' required type='text'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Name:
                        </td>
                        <td>
                            <input name='clientName' required type='text'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Street:
                        </td>
                        <td>
                            <input name='clientStreet' type='text'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client City, State, Zip:
                        </td>
                        <td>
                            <input name='clientAddress' type='text'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Phone:
                        </td>
                        <td>
                            <input name='clientPhone' type='tel' placeholder='(555)555-5555' pattern='([\d]{10}|[\d()-]{11,14})'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Client Email:
                        </td>
                        <td>
                            <input name='clientEmail' type='email' placeholder='client@url.tld'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Start Time:
                        </td>
                        <td>
                            <input name='showTimeStart' type='time'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            End Time:
                        </td>
                        <td>
                            <input name='showTimeEnd' type='time'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Number of Show Days:
                        </td>
                        <td>
                            <input name='showDays' type='number' min='1' placeholder='1'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Number of Rehearsal Days:
                        </td>
                        <td>
                            <input name='rehearsalDays' type='number' min='0' placeholder='0'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Number of Remotes ($available_remotes):
                        </td>
                        <td>
                            <input name='numRemotes' placeholder='0' type='number' max='$available_remotes'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Teleprompter:
                        </td>
                        <td>
                            <input name='teleprompter' type='checkbox'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Other Notes:
                        </td>
                        <td>
                            <input name='otherNotes' type='text'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Crew:
                        </td>
                        <td>";
                    $sql2 = 'SELECT crewid, firstName, lastName, crewCategory FROM crew where companyid = ? ORDER BY crewCategory';
                    if ($stmt2 = mysqli_prepare($link, $sql2)) {
                        mysqli_stmt_bind_param($stmt2, 's', $param_company);

                        $param_company = $_SESSION['company'];

                        // Bind variables to the prepared statement as parameters
                        //mysqli_stmt_bind_param($stmt, 's', $username);
                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($stmt2)) {
                            $recent_category = '';
                            mysqli_stmt_bind_result($stmt2, $crewid, $first_name, $last_name, $category);
                            while (mysqli_stmt_fetch($stmt2)) {
                                if ($category !== $recent_category) {
                                    $to_echo .= "<h4>$category:</h4>";
                                    $recent_category = $category;
                                }
                                $crew = "<input type='checkbox' name='crew$crewid'>$first_name $last_name<br>";
                                $to_echo .= $crew;
                            }

                        } else {
                            $error = mysqli_error($link);
                            echo "Something went wrong. Please try again later. $error";
                        }
                        mysqli_stmt_close($stmt2);
                    } else {
                        $error = mysqli_error($link);
                        echo "Something went wrong. Please try again later. $error";
                    }
                    $to_echo .= '<tr><td colspan="2" style="text-align: center">                <input class="btn btn-success" type="submit">
</td></tr>                    </tbody>
                </table>
            </form>';
                    echo $to_echo;
                }
                echo '</td>';
            }
        }

        echo '</tr></tbody>';
        mysqli_close($link);


        ?>


    </tbody>
</table>
