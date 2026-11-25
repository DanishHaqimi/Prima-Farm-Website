<?php
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == "admin" && $password == "123") {
        $_SESSION['admin'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Wrong username or password');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DessertByRanna Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-body">

<div class="login-box">
    <h1>ADMIN LOGIN</h1>

    <form method="POST">
        <label>USERNAME</label>
        <input type="text" name="username" required>

        <label>PASSWORD</label>
        <input type="password" name="password" required>

        <button type="submit" name="login">Login</button>
    </form>
</div>

</body>
</html>