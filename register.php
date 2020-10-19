<?php

require_once 'config/config.php';
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: index.php');
    exit;
}

// Define variables and initialize with empty values
$email = $password = $confirm_password = '';
$email_err = $password_err = $confirm_password_err = $captcha_err = '';

// Processing form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter a email.';
    } else {
        // Prepare a select statement
        $sql = 'SELECT uid FROM users WHERE email like ?';

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, 's', $param_email);

            // Set parameters
            $param_email = trim($_POST['email']);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    $email_err = 'This email is already taken.';
                } else {
                    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);;
                }
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } elseif (strlen(trim($_POST['password'])) < 8) {
        $password_err = 'Password must have at least 8 characters.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_err) && ($password !== $confirm_password)) {
            $confirm_password_err = 'Password did not match.';
        }
    }

    $whitelist = array(
        '127.0.0.1',
        '::1'
    );

    if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist, true)) {
        if (empty(trim($_POST['g-recaptcha-response']))) {
            $captcha_err = 'Please check the captcha.';
        } else {
            $captcha = $_POST['g-recaptcha-response'];
            $secretKey = CAPTCHA_PRIVATE_KEY;
            // post request to server
            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($captcha);
            $response = file_get_contents($url);
            $responseKeys = json_decode($response, true);
            // should return JSON with success as true
            if (!$responseKeys['success']) {
                $captcha_err = 'Failed captcha check';
            }
        }
    }


    // Check input errors before inserting in database
    if (empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($captcha_err)) {
        // Prepare an insert statement

            $sql = 'INSERT INTO users (email, password) VALUES (?, ?)';

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters

                mysqli_stmt_bind_param($stmt, 'ss', $param_email, $param_password);


            // Set parameters
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                header('location: login.php');
            } else {
                echo 'Something went wrong. Please try again later.';
            }
        }
        else{
            echo "Error on prepare";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <script src="resources/js/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="resources/css/bootstrap.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style type="text/css">
        body {
            font: 14px sans-serif;
        }

        .wrapper {
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper" align="center">
    <h2>Sign Up</h2>
    <p>Please fill this form to create an account.</p>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">

        <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
            <label>Email</label>
            <label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
            </label>
            <span class="help-block"><?php echo $email_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
            <label>Password</label>
            <label>
                <input type="password" name="password" minlength="8" class="form-control"
                       pattern="[\\\-\/a-zA-Z0-9._ +=()*&^%$,?<>!@#~`|]+"
                       title="8 characters minimum" value="<?php echo $password; ?>">
            </label>
            <span class="help-block"><?php echo $password_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
            <label>Confirm Password</label>
            <label>
                <input type="password" name="confirm_password" minlength="8" class="form-control"
                       pattern="[\\\-\/a-zA-Z0-9._ +=()*&^%$,?<>!@#~`|]+" value="<?php echo $confirm_password; ?>">
            </label>
            <span class="help-block"><?php echo $confirm_password_err; ?></span>
        </div>
        <div id="contact_options" class="form-group">

        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Submit">
            <input type="reset" class="btn btn-default" value="Reset">
        </div>
        <div class="g-recaptcha" data-sitekey="<?php echo CAPTCHA_PUBLIC_KEY; ?>"></div>
        <span class="help-block"><?php echo $captcha_err; ?></span>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>
</body>
<?php
include_once 'footer.php';
