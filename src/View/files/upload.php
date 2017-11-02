<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Upload form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post" enctype="multipart/form-data">
<input type="file" name="some1" />
<input type="file" name="some2" />
<label>
    <input type="checkbox" name="public" value="1" checked />
    Should be accesible for public
</label>
<input type="submit" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
