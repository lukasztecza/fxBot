<?php include(__DIR__ . '/../common/head.php'); ?>
<p>Edit form</p>
<p class="error"><?php echo $error; ?></p>
<form method="post">
<input type="text" name="name" value="<?php echo $item['name'] ?? null; ?>" />
<input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
<input type="submit" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
