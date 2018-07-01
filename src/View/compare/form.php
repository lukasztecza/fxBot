<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Compare form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post">
    <input type="text" name="type" placeholder="Type (bank)" /><br />
    <input type="text" name="instrument" placeholder="Instrument (EUR_USD)" /><br />
    <input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
    <input type="submit" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
