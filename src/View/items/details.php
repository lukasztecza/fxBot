<?php include(__DIR__ . '/../common/head.php'); ?>
<div>
    <p>Item details</p>
    <?php if ($item): ?>
        <p><?php echo $item['name']; ?> <a href="<?php echo '/items/' . $item['id'] . '/edit'; ?>">Edit</a></p>
    <?php endif; ?>
</div>
<?php include(__DIR__ . '/../common/foot.php'); ?>
