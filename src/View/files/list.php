<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Files list</h3>
<form method="post">
<p>Images:</p>
<ul>
    <?php foreach ($images as $image): ?>
        <li>
            <img width="100px" height="100px" src="<?php echo '/upload/images/' . $image['name'] . '.' . $image['extension']; ?>" />
            <input type="checkbox" name="ids[]" value="<?php echo $image['id']; ?>" />
        </li>
    <?php endforeach; ?>
</ul>
<p>Files:</p>
<ul>
    <?php foreach ($otherFiles as $file): ?>
        <li>
            <a href="<?php echo '/upload/files/' . $file['name'] . '.' . $file['extension']; ?>"><?php echo $file['name']; ?></a>
            <input type="checkbox" name="ids[]" value="<?php echo $file['id']; ?>" />
        </li>
    <?php endforeach; ?>
</ul>
<input type="submit" value="Delete selected" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
