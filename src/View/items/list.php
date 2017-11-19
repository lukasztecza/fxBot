<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Items list</h3>
<p class="error"><?php echo $error; ?></p>
<?php $prefix = '/items/list/'; include(__DIR__ . '/../common/paginator.php'); ?>
<form method="post">
<ul>
    <?php foreach ($items as $item): ?>
        <li>
            <input type="checkbox" name="ids[]" value="<?php echo $item['id']; ?>" />
            <?php echo $item['name'] ?>
            <a href="<?php echo '/items/' . $item['id'] . '/edit'; ?>">Edit</a>
            <a href="<?php echo '/items/' . $item['id']; ?>">Details</a>
        </li>
    <?php endforeach; ?>
</ul>
<input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
<input type="submit" value="Delete selected" />
</form>
<?php include(__DIR__ . '/../common/foot.php'); ?>
