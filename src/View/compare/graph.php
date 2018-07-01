<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Graph for <?php echo $type; ?></h3>
<?php foreach ($comparison as $comp): ?>
    <?php echo $comp['datetime'] . ' ' . $comp['priceInstrument'] . ' ' . $comp['price'] . ' ' . $comp['instrument'] . ' ' . $comp['actual']; ?> <br>
<?php endforeach; ?>
<?php include(__DIR__ . '/../common/foot.php'); ?>
