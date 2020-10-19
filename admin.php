<?php
include_once 'header.php';
if (!checkSession('loggedin')) {
    header('location: login.php');
}
if (!checkAdminOrPermission('admin')) {
    header('location: /');
}
if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['companyName'], $_POST['studios'], $_POST['resources'], $_POST['crewCategories'])) {
    error_log("hey");
    if (empty($_POST['companyName'])) {
        $company_name_err = 'Empty company name';
    }
    else{
        $company_name = $_POST['companyName'];
    }
    if (empty($_POST['resources'])) {
        $resources_err = 'Empty company name';
    }
    else{
        $resources = $_POST['resources'];
    }
    if(empty($_POST['studios'])){
        $studios_err = 'Empty Studios';
    }
    else{
        try {
            if(substr($_POST['studios'], -1) === ','){
                $studios = substr($_POST['studios'], 0, -1);
            }
            else{
                $studios = $_POST['studios'];
            }
            $studios = json_encode(explode(',', $studios), JSON_THROW_ON_ERROR, 512);

        }
        catch (Exception $e){
            $studios_err = 'Error encoding studios. ' . $e;
        }
    }
    if(empty($_POST['crewCategories'])){
        $crew_categories_err = 'Empty Studios';
    }
    else{
        try {
            if(substr($_POST['crewCategories'], -1) === ','){
                $crew_categories = substr($_POST['crewCategories'], 0, -1);
            }
            else{
                $crew_categories = $_POST['crewCategories'];
            }
            $crew_categories = json_encode(explode(',', $crew_categories), JSON_THROW_ON_ERROR, 512);

        }
        catch (Exception $e){
            $crew_categories_err = 'Error encoding studios. ' . $e;
        }
    }
    if(empty($company_name_err) && empty($studios_err) && empty($crew_categories_err) && empty($resources_err)){
        $sql = 'UPDATE settings SET company_name = ?, studios = ?, resources = ?, crew_categories = ? WHERE companyid = ?';

        if ($stmt = mysqli_prepare($link, $sql)) {
// Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, 'ssssi', $company_name, $studios, $resources, $crew_categories, $_SESSION['company']);
            if (!mysqli_stmt_execute($stmt)) {
                echo 'There was an error processing your request.';
            } else {
                header('Location: ' . $_SERVER['PHP_SELF']);

            }
        }
        else{
            echo mysqli_error($link);
        }
    }

}
else{
    $sql = 'SELECT company_name, studios, resources, crew_categories FROM settings WHERE companyid = ?';
    if ($stmt = mysqli_prepare($link, $sql)) {
// Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['company']);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $company_name, $studios, $resources, $crew_categories);
            if (!mysqli_stmt_fetch($stmt)) {
                echo 'Error fetching data';
            } else {
                $studios = json_decode($studios);
                $crew_categories = json_decode($crew_categories);
            }
        }
    }
}

?>
<title>Admin Page</title>
</head>
<body style="width: 100%; height: 100%">
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
    <table class="table-striped" border="1" style="height: 100%; margin: auto">
        <thead>
        <tr>
            <th colspan="2" style="text-align: center"><h2>Settings</h2></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                Company Name:
            </td>
            <td>
                <input type="text" name="companyName" value="<?php
                if (isset($company_name)) {
                    echo htmlspecialchars($company_name);
                }
                ?>">
                <span class="help-block"><?php if (isset($company_name_err)) {
                        echo $company_name_err;
                    } ?></span>
            </td>
        </tr>
        <tr>
            <td>
                Studios:<br>
                (Separate by comma)
            </td>
            <td>
                <input type="text" name="studios" value="<?php
                if (isset($studios)) {
                    foreach ($studios as $studio) {
                        echo $studio . ',';
                    }
                }
                ?>">
                <span class="help-block"><?php if (isset($studios_err)) {
                        echo $studios_err;
                    } ?></span>
            </td>
        </tr>
        <tr>
            <td>
                Resources:
            </td>
            <td>
                <input type="text" name="resources" value="<?php
                if (isset($resources)) {
                    echo $resources;
                }
                ?>">
                <span class="help-block"><?php if (isset($resources_err)) {
                        echo $resources_err;
                    } ?></span>
            </td>
        </tr>
        <tr>
            <td>
                Crew Categories:<br>
                (Separate by comma)
            </td>
            <td>
                <input type="text" name="crewCategories" value="<?php
                if (isset($crew_categories)) {
                    foreach ($crew_categories as $category) {
                        echo $category . ',';
                    }
                }
                ?>">
                <span class="help-block"><?php if (isset($crew_categories_err)) {
                        echo $crew_categories_err;
                    } ?></span>
            </td>
        </tr>
        <tr>
            <td style="text-align: center" colspan="2">
                <button class="btn btn-success" type="submit">Submit</button>
            </td>
        </tr>
        </tbody>
    </table>
</form>
</body>
