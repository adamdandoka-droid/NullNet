<?php
  ob_start();
  session_start();
  include "includes/config.php";
  date_default_timezone_set('UTC');


  if(isset($_SESSION['sname']) and isset($_SESSION['spass'])){
    $_chkU = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
    $_chkQ = mysqli_query($dbcon, "SELECT id FROM users WHERE username='$_chkU'");
    if($_chkQ && mysqli_num_rows($_chkQ) > 0){
      header("location: index.html");
      exit();
    } else {
      session_unset(); session_destroy(); session_start();
    }
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="utf-8">
<title>NullNet - Shop Register</title>
<link rel="shortcut icon" href="img/favicon.ico" />
<link rel="stylesheet" type="text/css" href="files/bootstrap/3/css/bootstrap.css" />
<link rel="stylesheet" href="files/css/auth.css">
<link rel="stylesheet" href="files/css/mobile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script type="text/javascript" src="files/js/jquery.js"></script>
<script type="text/javascript" src="files/bootstrap/3/js/bootstrap.js"></script>
</head>
<body>
<div class="container">
<div class="row">
<form class="login" method="post" action="signupform.html">
            <h4><i class="fa-solid fa-fire" style="color:#910606;margin-right:6px;"></i> NULLNET &mdash; Register</h4>
            <?php
                if(isset($_GET['error']) AND !empty($_GET['error'])) {
                    $error = $_GET['error'];

                    if ($error == "userexist") {
                        echo "<div class='alert alert-dismissible alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Username already exists!</p></div>";
                    }

                    if ($error == "emailexist") {
                        echo "<div class='alert alert-dismissible alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Email already exists!</p></div>";
                    }

                    if ($error == "passnotmatch") {
                        echo "<div class='alert alert-dismissible alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Passwords do not match!</p></div>";
                    }

                    if ($error == "passlength") {
                        echo "<div class='alert alert-dismissible alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Password must be more than 6 and less than 16.</p></div>";
                    }
                }
            ?>

            <div class="auth-field">
                <input type="text" name="username" placeholder="Username" required>
                <i class="fa-solid fa-user auth-icon"></i>
            </div>

            <div class="auth-field">
                <input type="password" name="password_signup" placeholder="Password" required>
                <i class="fa-solid fa-lock auth-icon"></i>
            </div>

            <div class="auth-field">
                <input type="password" name="password_signup2" placeholder="Confirm Password" required>
                <i class="fa-solid fa-lock auth-icon"></i>
            </div>

            <div class="auth-field">
                <input type="email" name="email" placeholder="Email" required>
                <i class="fa-solid fa-envelope auth-icon"></i>
            </div>

            <div class="auth-actions">
                <button type="submit" id="divButton">Register</button>
                <button type="button" class="register" onclick="window.location.href = 'login.html'">Login</button>
            </div>
            </form>

    </div>
</div>
</body>
</html>
