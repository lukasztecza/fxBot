<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Login form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post">
    <input type="text" name="username" placeholder="Username" /><br />
    <input type="password" name="password" placeholder="Password" /><br />
    <input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
    <input type="submit" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
