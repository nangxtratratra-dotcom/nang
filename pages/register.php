<?php
$nameErr = $usernameErr = $passwdErr = '';
$name = $username = '';
if (isset($_POST['name'], $_POST['username'], $_POST['passwd'], $_POST['confirmPasswd'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $passwd = trim($_POST['passwd']);
    $confirmPasswd = trim($_POST['confirmPasswd']);
    if (empty($name)) {
        $nameErr = 'please input name!';
    }
    if (empty($username)) {
        $usernameErr = 'please input username!';
    }
    if (empty($passwd)) {
        $passwdErr = 'please input password!';
    }
    if ($passwd !== $confirmPasswd) {
        $passwdErr = 'password does not match!';
    }
    if (usernameExists($username)) {
        $usernameErr = 'please choose another username !';
    }
    if (empty($nameErr) && empty($usernameErr) && empty($passwdErr)) {
        if (registerUser($name, $username, $passwd)) {
            $name = $username = '';
            echo '<div class="alert alert-success" role="alert">
            Registration successful! You can now 
            <a href="./?page=login" class="alert-link">login here</a>.
            </div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">
            Registration failed! Please try again.
            </div>';
        }
    }
}

?>

<form method="post" action="./?page=register" class="col-md-10 col-lg-6 mx-auto">
    <h3>Register Page</h3>
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" value="<?php echo $name ?>" type="text" class="form-control
        <?php echo empty($nameErr) ? '' : 'is-invalid' ?>">
        <div class="invalid-feedback"><?php echo $nameErr ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input name="username" value="<?php echo $username ?>" type="text" class="form-control
        <?php echo empty($usernameErr) ? '' : 'is-invalid' ?>">
        <div class="invalid-feedback"><?php echo $usernameErr ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="passwd" type="password" class="form-control
        <?php echo empty($passwdErr) ? '' : 'is-invalid' ?>">
        <div class="invalid-feedback"><?php echo $passwdErr ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input name="confirmPasswd" type="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>