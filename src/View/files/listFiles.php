<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Files list</h3>
<p class="error"><?php echo $error; ?></p>
<?php if(isset($flash)): ?>
    <p class="<?php echo $flash['type']; ?>"><?php echo $flash['text']; ?></p>
<?php endif; ?>
<?php $prefix = '/files/list/' . $type . '/'; include(__DIR__ . '/../common/paginator.php'); ?>
<form method="post">
<p>Files:</p>
<ul>
    <?php foreach ($files as $file): ?>
        <li>
            <a href="<?php echo ($private ? '/private' : '') . '/upload/files/' . $file['name']; ?>"><?php echo $file['name']; ?></a>
            <input type="checkbox" name="ids[]" value="<?php echo $file['id']; ?>" />
        </li>
    <?php endforeach; ?>
</ul>
<input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
<input type="submit" value="Delete selected" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
