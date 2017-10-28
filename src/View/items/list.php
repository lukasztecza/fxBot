<?php include(__DIR__ . '/../common/head.php'); ?>
<div>
    <p>Items list</p>
    <ul>
        <?php foreach ($items as $item): ?>
            <li><?php echo $item['name'] ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include(__DIR__ . '/../common/foot.php'); ?>
