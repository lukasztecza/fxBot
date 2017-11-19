<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Files menu</h3>
<ul>
    <?php foreach($types as $type => $name): ?>
        <li><a href="<?php echo '/files/list/' . $type . '/1'; ?>"><?php echo $name; ?></a></li>
    <?php endforeach; ?>
</ul>
<?php include(__DIR__ . '/../common/foot.php'); ?>
