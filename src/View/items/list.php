<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Items list</h3>
<p class="error"><?php echo $error; ?></p>
<ul>
    <?php for($i = 1; $i <= $pages; $i++): ?>
        <li>
            <?php if($i === (int)$page): ?>
                <?php echo $i ?>
            <?php else: ?>
                <a href="<?php echo '/items/list/' . $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        </li>
    <?php endfor; ?>
</ul>
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
