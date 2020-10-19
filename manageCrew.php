<?php

include_once 'header.php';
if(!checkSession('loggedin')){
    header('location: login.php');
}
if (!checkAdminOrPermission('manageCrew')) {
    die('Missing required permissions. Please contact your company admin');
}
if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['crewCategory'], $_POST['firstName'], $_POST['lastName'])) {
    if (empty(trim($_POST['firstName']))) {
        $firstName_err = 'Please enter a first name.';
    } else {
        $firstName = trim($_POST['firstName']);
    }
    if (empty(trim($_POST['lastName']))) {
        $lastName_err = 'Please enter a last name.';
    } else {
        $lastName = trim($_POST['lastName']);
    }

    if (empty(trim($_POST['crewCategory']))) {
        $crewCategory_err = 'Please enter a crewCategory.';
    } else {
        // Prepare a select statement
        $sql = 'SELECT crew_categories FROM settings WHERE companyid = ?';
        $crewCategory = trim($_POST['crewCategory']);
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['company']);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                //store result
                mysqli_stmt_bind_result($stmt, $currentCategories);
                mysqli_stmt_fetch($stmt);

                $currentCategories = json_decode($currentCategories, true);
                if (!in_array($crewCategory, $currentCategories, true)) {
                    $currentCategories[] = $crewCategory;
                    $currentCategories = json_encode($currentCategories);
                    $sql2 = 'UPDATE settings SET crew_categories = ? WHERE companyid = ?';
                    $link2 = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
                    if ($stmt2 = mysqli_prepare($link2, $sql2)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt2, 'si', $currentCategories, $_SESSION['company']);
                        // Attempt to execute the prepared statement
                        if (!mysqli_stmt_execute($stmt2)) {
                            echo 'Something went wrong. Please try again later.';

                        }

                        mysqli_stmt_close($stmt2);
                    } else {
                        echo 'Oops! Something went wrong preparing the crewCategory insert. Please try again later.';
                    }
                    mysqli_close($link2);
                }

            } else {
                echo 'Oops! Something went wrong fetching categories. Please try again later.';
            }
        } else {
            echo 'Error preparing statement';
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
    if (isset($_POST['phone'])) {
        $_POST['phone'] = str_replace(' ', '', $_POST['phone']);
        if(!empty(trim($_POST['phone'])) && strlen(trim($_POST['phone'])) < 10) {
            $phone_err = 'Phone seems to be too short';
            $phone = trim($_POST['phone']);
        }
        elseif(preg_match('([\d]{10}|[\d()-]{11,14})', $_POST['phone'])){
            $phone = $_POST['phone'];
        }
    }
if (isset($_POST['crewid']) && !empty(trim($_POST['crewid']))) {

    $crewid = $_POST['crewid'];
}
    if (isset($_POST['email'])  && !empty(trim($_POST['email']))) {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    }
    // Check input errors before inserting in database
    if (empty($firstName_err) && empty($lastName_err) && empty($crewCategory_err) &&empty($phone_err)) {


        // Prepare an insert statement
        if (isset($crewid)) {


            $sql = 'UPDATE crew SET crewCategory = ?, firstName = ?, lastName = ?, email = ?, phone = ?, last_update_uid = ? WHERE crewid = ? and companyid = ?';
            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, 'sssssisi', $crewCategory, $firstName, $lastName, $email, $phone, $_SESSION['uid'], $crewid, $_SESSION['company']);


                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    header('Location: ' .$_SERVER['PHP_SELF']);

                } else {
                    echo 'Something went wrong. Please try again later.';
                    echo mysqli_error($link);

                }
            }
            else{
                echo 'Error preparing statement';
                echo mysqli_error($link);
            }
            // Close statement
            mysqli_stmt_close($stmt);
        } else {

            $sql = 'INSERT INTO crew (crewCategory, firstName, lastName, email, phone, input_uid, companyid) VALUES (?, ?, ?, ?, ?, ?, ?)';
            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, 'sssssii', $crewCategory, $firstName, $lastName, $email, $phone, $_SESSION['uid'], $_SESSION['company']);

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    header('Location: ' .$_SERVER['PHP_SELF']);


                } else {
                    echo 'Something went wrong. Please try again later.';
                    echo mysqli_error($link);
                }
            }
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }


}

?>
<script src="resources/js/list.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="resources/css/admin.css">

</head>
<body>
<div id="crew-list" style="text-align: center">

    <table id="crewTable" class='table-striped'>
        <thead>
        <tr>
            <td class="sort" data-sort="crewid" style="display: none">ID</td>
            <td class="sort" data-sort="crewCategory">Crew Category</td>
            <td class="sort" data-sort="firstName">First Name</td>
            <td class="sort" data-sort="lastName">Last Name</td>
            <td class="sort" data-sort="email">Email</td>
            <td class="sort" data-sort="phone">Phone</td>
            <td colspan="2" ><input type="text" class="search" placeholder="Search" /></td>
        </tr>
        </thead>
        <tbody class="list">
        <?php

        $sql = 'SELECT crewid, crewCategory, firstName, lastName, email, phone FROM crew WHERE companyid = ?';


        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $param_company);

            $param_company = $_SESSION['company'];
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $crewidTable, $crewCategoryTable, $firstNameTable, $lastNameTable, $emailTable, $phoneTable);


                while (mysqli_stmt_fetch($stmt)) {
                    echo "<tr><td class='crewid' style='display:none;'>$crewidTable</td>";
                    echo "<td class='crewCategory'>$crewCategoryTable</td>";
                    echo "<td class='firstName'>$firstNameTable</td>";
                    echo "<td class='lastName'>$lastNameTable</td>";
                    echo "<td class='email'>$emailTable</td>";
                    echo "<td class='phone'>$phoneTable</td>";
                    echo "<td class='edit'><button class='edit-item-btn'>Edit</button></td>";
                    echo "<td class='remove'><button class='remove-item-btn'>Remove</button></td></tr>";
                }


            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
        }
        mysqli_stmt_close($stmt);


        ?>

        </tbody>
    </table>
</div>
<div id="singleTableDiv">
    <form id="singleTableForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <table align="center" id="singleTable">
            <tbody>
            <tr>
                <td class="crewid" style="display: none">                    <input type="hidden" name="crewid" id="crewid-field" value="<?php if (isset($crewid)) {
                        echo htmlspecialchars($crewid);
                    } ?>"/>
                </td>
                <td class="crewCategory">
                    <input list="crewCategories" id="crewCategory-field" placeholder="Crew Category" size="15"
                           name="crewCategory"
                           value="<?php if (isset($crewCategory)) {
                               echo htmlspecialchars($crewCategory);
                           } ?>">
                    <datalist id="crewCategories">
                        <?php
                        $sql = 'SELECT crew_categories FROM settings WHERE companyid = ?';

                        if ($stmt = mysqli_prepare($link, $sql)) {
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['company']);


                            // Attempt to execute the prepared statement
                            if (mysqli_stmt_execute($stmt)) {

                                /* store result */
                                mysqli_stmt_bind_result($stmt, $crew_categories);
                                if (mysqli_stmt_fetch($stmt)) {

                                    if ($crew_categories !== null) {

                                        $crew_categories = json_decode($crew_categories, true);

                                    }
                                    foreach ($crew_categories as $option) {

                                        $option = htmlspecialchars($option);
                                        echo "<option value='$option'>";
                                    }
                                }
                                else{
                                    error_log(mysqli_error($link));
                                }


                            } else {
                                error_log(mysqli_error($link));
                                echo 'Oops! Something went wrong. Please try again later.';
                            }
                            mysqli_stmt_close($stmt);
                        }
                        else{
                            error_log(mysqli_error($link));
                        }
                        mysqli_close($link);
                        ?>
                    </datalist>
                    <span class="help-block"><?php if (isset($crewCategory_err)) {
                            echo $crewCategory_err;
                        } ?></span>
                </td>
                <td>
                    <input type="text" id="firstName-field" name="firstName" size="10" pattern="[a-zA-Z]+"
                           placeholder="First Name"
                           value="<?php if (isset($firstName)) {
                               echo htmlspecialchars($firstName);
                           } ?>"/>
                    <span class="help-block"><?php if (isset($firstName_err)) {
                            echo $firstName_err;
                        } ?></span>
                </td>
                <td>
                    <input type="text" id="lastName-field" name="lastName" size="15" pattern="[a-zA-Z]+"
                           placeholder="Last Name"
                           value="<?php if (isset($lastName)) {
                               echo $lastName;
                           } ?>"/>
                    <span class="help-block"><?php if (isset($lastName_err)) {
                            echo $lastName_err;
                        } ?></span>
                </td>
                <td>
                    <label for="email-field"></label><input type="email" id="email-field" size="30" name="email" placeholder="Email"
                                                            value="<?php if (isset($email)) {
                                                                echo $email;
                                                            } ?>"
                    />
                    <span class="help-block"><?php if (isset($email_err)) {
                            echo $email_err;
                        } ?></span>
                </td>
                <td>
                    <label for="phone-field"></label><input type="tel" id="phone-field" size="15" name="phone"
                                                            placeholder="Phone Number"
                                                            value="<?php if (isset($phone)) {
                                                                echo $phone;
                                                            } ?>"/>
                    <span class="help-block"><?php if (isset($phone_err)) {
                            echo $phone_err;
                        } ?></span>
                </td>
                <td class="add">
                    <div class="form-group">
                        <input type="submit" id="editSubmitBtn" class="btn btn-primary" value="<?php if (isset($firstName)) {
                            echo 'Edit';
                        }
                        else{
                            echo 'Add';
                        }?>">
                        <input type="reset" onclick="resetButton()"
                               class="btn btn-default" value="Reset">
                    </div>
                </td>
            </tr>
            </tbody>

        </table>

    </form>
</div>
<script>
    const options = {
        valueNames: ['crewid', 'crewCategory', 'firstName', 'lastName', 'email', 'phone']
    };

    // Init list
    var crewList = new List('crew-list', options);

    var crewidField = $('#crewid-field'),
        crewCategoryField = $('#crewCategory-field'),
        firstNameField = $('#firstName-field'),
        lastNameField = $('#lastName-field'),
        emailField = $('#email-field'),
        phoneField = $('#phone-field'),
        addBtn = $('#editSubmitBtn'),
        editBtn = $('#edit-btn').hide(),
        removeBtns = $('.remove-item-btn'),
        editBtns = $('.edit-item-btn');

    // Sets callbacks to the buttons in the list
    refreshCallbacks();

    editBtn.click(function () {

        var item = crewList.get('crewid', crewidField.val())[0];
        item.values({
            crewid: crewidField.val(),
            crewCategory: crewCategoryField.val(),
            firstName: firstNameField.val(),
            lastName: lastNameField.val(),
            email: emailField.val(),
            phone: phoneField.val()
        });
        clearFields();
        editBtn.hide();
    });

    function refreshCallbacks() {
        crewList.update();
        // Needed to add new buttons to jQuery-extended object
        //removeBtns = $(removeBtns.selector);

        //editBtns = $(editBtns.selector);

        removeBtns.click(function () {
            console.log($(this).closest('tr').find('.crewid').text());
            var itemId = $(this).closest('tr').find('.crewid').text();
            $.ajax({
                type: "POST",
                url: 'removeCrew.php',
                data: {'crewid': itemId},
                success: crewList.remove('crewid', itemId),
            });
            $.ajax( "removeCrew.php", { crewid: itemId})
                .done(function() {
                    crewList.remove('crewid', itemId)
                })
            .fail(function (){
                alert("Failed to remove crew member")
            });

        });

        editBtns.click(function () {
            var itemId = $(this).closest('tr').find('.crewid').text();
            console.log($(this).closest('tr').find('.crewid').text());
            console.log(crewList.get('crewid', itemId));
            var itemValues = crewList.get('crewid', itemId)[0].values();
            crewidField.val(itemValues.crewid);
            crewCategoryField.val(itemValues.crewCategory);
            firstNameField.val(itemValues.firstName);
            lastNameField.val(itemValues.lastName);
            emailField.val(itemValues.email);
            phoneField.val(itemValues.phone);
            document.getElementById('editSubmitBtn').value = 'Edit';
        });
    }
    function resetButton() {
        document.getElementById('editSubmitBtn').value = 'Add';
        clearFields();
    }

    function clearFields() {
        document.getElementById('singleTableForm').reset();
       crewidField.val('');

    }</script>
</body>
