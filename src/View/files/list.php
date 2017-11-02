<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Files list</h3>
<form method="post">
<ul>
    <?php foreach ($files as $file): ?>
        <li>
            <img width="100px" height="100px" src="<?php echo '/upload/images/' . $file['name'] . '.' . $file['extension'] ; ?>" />
            <input type="checkbox" name="ids[]" value="<?php echo $file['id']; ?>" />
        </li>
    <?php endforeach; ?>
</ul>
<input type="submit" value="Delete selected" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
