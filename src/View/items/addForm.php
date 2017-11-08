<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Add form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post">
    <input type="button" value="Add input" />
    <div>
        <input type="text" name="items[]" /><br />
    </div>
    <input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
    <input type="submit" />
</form>
<script src="http://code.jquery.com"></script><!-- @TODO test if content restriction works -->
<script src="/assets/js/core.js<?php echo $assetsVersioning; ?>"></script>
<?php include(__DIR__ . '/../common/foot.php'); ?>
