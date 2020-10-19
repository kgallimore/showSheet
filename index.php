<?php
include_once 'header.php';

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
if ($_SERVER['REQUEST_URI'] !== '/' && $month === date('n') && $year === date('Y')) {
    header('location: /');
}
if(isset($_SESSION['company'])){
    $company = $_SESSION['company'];
}

$month_name = date('F', mktime(0, 0, 0, $month, 10));
$start_day = date('l', mktime(0, 0, 0, $month, 1, $year));

$day_of_week_start_day_num = date('w', strtotime($start_day));
$days_in_month = days_in_month($month, $year);
if ($month < 12) {
    $next_Month = $month + 1;
    $next_Year = $year;
} else {
    $next_Month = 1;
    $next_Year = $year + 1;
}
if ($month > 1) {
    $previous_Month = $month - 1;
    $previous_Year = $year;
} else {
    $previous_Month = 12;
    $previous_Year = $year - 1;
}
$days_in_previous_month = days_in_month($previous_Month, $previous_Year);
echo "<title>$month_name $year Show Sheet Calendar</title>"
?>

<style type="text/css">
    html, body {
        font: 14px sans-serif;
        width: 100%;
        height: 100%;
    }


    .calendar-table {
        height: 100%;
        width: 100%;
        margin: auto;
        border: 1px solid black;
    }
.calendar-table-container{
    height: 90%;
    width: 100%;
    margin: auto;
}
    .calendar-th {
        height: 1%;
        text-align: center;
        border: 1px solid black;
    }

    .previousMonthTd {
        position: relative;
        border: 1px solid black;
        width: 14%;
        color: lightgrey;
        left: 0;
    }

    .currentMonthTd {
        position: relative;
        border: 1px solid black;
        width: 14%;
        left: 0;
    }

    .bookingHrefCurrentMonth {
        top: 0;
        left: 0;
        position: absolute;
    }

    .bookingHrefPreviousMonth {
        opacity: 95%;
        top: 0;
        left: 0;
        position: absolute;
    }
</style>

</head>
<body>

<?php
$previous_Month_Button = "<button style='position: absolute; left: 0' class='btn' >Previous Month</button>";
//echo $previous_Month_Button;
$next_Month_Button = "<button style='position: absolute; right: 0' class='btn' onclick='window.location.href=\"?year=$next_Year&month=$next_Month\"'>Next Month</button>";
//echo $next_Month_Button;
?>
<table class="calendar-table-container">
    <tbody>
    <tr>
        <td style="width: 32px">
            <?php
            $previous_Month_Button = "<button style='background-size: 32px; height: 50%; width: 32px; background: url(resources/images/arrow_left.svg) no-repeat center;' onclick='window.location.href=\"?year=$previous_Year&month=$previous_Month\"'></button>";
            echo $previous_Month_Button;
            ?>
        </td>
        <td style="width: 100%">
            <table class="calendar-table">
                <tbody>
                <tr>
                    <th style="font-size: 24px" class="calendar-th" colspan="7"><?php echo $month_name ?></th>
                </tr>
                <tr>
                    <th class="calendar-th">Sunday</th>
                    <th class="calendar-th">Monday</th>
                    <th class="calendar-th">Tuesday</th>
                    <th class="calendar-th">Wednesday</th>
                    <th class="calendar-th">Thursday</th>
                    <th class="calendar-th">Friday</th>
                    <th class="calendar-th">Saturday</th>
                </tr>
                <tr>
                    <?php
                    if(isset($company)){
                        $sql = 'SELECT studios FROM settings WHERE companyid = ?';
                        if ($stmt = mysqli_prepare($link, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt, 'i', $company);
                            if (mysqli_stmt_execute($stmt)) {
                                //store result
                                mysqli_stmt_bind_result($stmt, $studios);
                                mysqli_stmt_fetch($stmt);
                                $studios = json_decode($studios, true);
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }

                    for ($current_day = 0; $current_day <= $days_in_month + $day_of_week_start_day_num - 1; $current_day++) {
                        if ($current_day % 7 === 0 && $current_day !== 0) {
                            echo '</tr><tr>';
                        }
                        if ($current_day >= $day_of_week_start_day_num) {
                            $day_of_month = $current_day - $day_of_week_start_day_num + 1;

                            $cell = "<td class='currentMonthTd'><button class='bookingHrefCurrentMonth' onclick='window.location.href=\"booking.php?year=$year&month=$month&day=$day_of_month\"'>$day_of_month</button>";
                            $formatted_date = date('Ymd', mktime(0, 0, 0, $month, $day_of_month, $year));
                            if(isset($company)){
                                $show_array = array();

                                foreach ($studios as $studio){
                                    $sql = 'SELECT show_name, show_start_date, rehearsals_start_date FROM shows WHERE companyid = ? AND studio = ? AND rehearsals_start_date <= ? AND ? <= shows_end_date';
                                    if ($stmt = mysqli_prepare($link, $sql)) {

                                        // Bind variables to the prepared statement as parameters
                                        mysqli_stmt_bind_param($stmt, 'ssss', $_SESSION['company'], $studio, $formatted_date, $formatted_date);
                                        if (mysqli_stmt_execute($stmt)) {
                                            //store result
                                            mysqli_stmt_bind_result($stmt, $show_name, $show_start_date, $rehearsals_start_date);

                                            if (mysqli_stmt_fetch($stmt)) {
                                                $show_array[$studio] = ['show_name' => $show_name,'show_start_date' => $show_start_date, 'rehearsals_start_date' => $rehearsals_start_date];

                                                //$cell .= "$studio: $show_name<br>";


                                            }
                                        }
                                    }
                                    mysqli_stmt_close($stmt);




                                }
                                if($show_array){
                                    $show_table = '<table style="width: 100%; text-align: center; top: 24px; position: absolute"><thead><tr>';
                                    $show_table_body = '<tbody><tr>';
                                    $thead_width = 100 / count($show_array);
                                    foreach ($show_array as $show_studio => $show_data){
                                        $show_name = $show_data['show_name'];
                                        if(strtotime($formatted_date) - strtotime($show_data['show_start_date']) >= 0){
                                            $show_name .= ' (Show)';
                                        }else{
                                            $show_name .= ' (Rehearsal)';
                                        }
                                        $show_table .= "<th style='width: $thead_width%; text-align: center'>$show_studio</th>";
                                        $show_table_body .= "<td>$show_name</td>";
                                    }
                                    $show_table_body .= '</tr></tbody>';
                                    $show_table .= $show_table_body . '</tr></thead></table>';
                                    $cell .= $show_table;
                                }
                            }


                            $cell .= '</td>';
                            //echo '<td class="currentMonth"><a href="booking.php?month=" ' . $day_of_month . '</td>';

                        } else {
                            $day_of_month = $days_in_previous_month - ($day_of_week_start_day_num - $current_day) + 1;
                            $cell = "<td class='previousMonthTd'><button class='bookingHrefPreviousMonth' onclick='window.location.href=\"booking.php?year=$previous_Year&month=$previous_Month&day=$day_of_month\"'>$day_of_month</button></td>";

                        }
                        echo $cell;
                    }
                    ?>
                </tbody>
            </table>
        </td>
        <td style="width: 32px">
            <?php
            $previous_Month_Button = "<button style='background-size: 32px; height: 50%; width: 32px; background: url(resources/images/arrow_right.svg) no-repeat center;' onclick='window.location.href=\"?year=$next_Year&month=$next_Month\"'></button>";
            echo $previous_Month_Button;
            ?>
        </td>
    </tr>
    </tbody>
</table>

<?php
include_once 'footer.php';