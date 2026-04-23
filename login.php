<?php
  ob_start();
  session_start();
  include "includes/config.php";
  date_default_timezone_set('UTC');


  if(isset($_SESSION['sname']) and isset($_SESSION['spass'])){
   header("location: index.html");
   exit();
}
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="utf-8">
<title>NullNet-Shop Login</title>
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

            <form class="login" method="post" action="loginform.html">
            <h4><i class="fa-solid fa-fire" style="color:#910606;margin-right:6px;"></i> NULLNET &mdash; Login</h4>
            <?php
                if(isset($_GET['error'])) {
                 echo "<div class='alert alert-dismissible alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Login failed! Please try again!</p></div>";
                }

                if (isset($_GET['success']) AND $_GET['success'] == "register") {
                    echo "<div class='alert alert-dismissible alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button><p>Successfully registered! Login now.</p></div>";
                }
            ?>

            <div class="auth-field">
                <input type="text" name="user" placeholder="Username" required>
                <i class="fa-solid fa-user auth-icon"></i>
            </div>

            <div class="auth-field">
                <input type="password" name="pass" placeholder="Password" required>
                <i class="fa-solid fa-lock auth-icon"></i>
            </div>

            <div class="auth-actions">
                <button type="submit" id="divButton">Login</button>
                <button type="button" class="register" onclick="window.location.href = 'signup.html'">Register</button>
            </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>
