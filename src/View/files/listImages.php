<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Images list</h3>
<p class="error"><?php echo $error; ?></p>
<?php $prefix = '/files/list/' . $type . '/'; include(__DIR__ . '/../common/paginator.php'); ?>
<form method="post">
<p>Images:</p>
<ul>
    <?php foreach ($files as $file): ?>
        <li>
            <a href="<?php echo ($private ? '/private/images/' : '/upload/images/') . $file['name']; ?>"><noscript><img
                width="100px"
                height="100px"
                src="<?php echo ($private ? '/private/images/' : '/upload/images/') . $file['name']; ?>"
                alt="<?php echo $file['name']; ?>"
            /></noscript><img
                width="100px"
                height="100px"
                <?php /* move it to data-src */ ?>
                src="<?php echo ($private ? '/private/images/' : '/upload/images/') . $file['name']; ?>"
                alt="<?php echo $file['name']; ?>"
                data-src=""
            /></a>
            <input type="checkbox" name="ids[]" value="<?php echo $file['id']; ?>" />
        </li>
    <?php endforeach; ?>
</ul>
<input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
<input type="submit" value="Delete selected" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
