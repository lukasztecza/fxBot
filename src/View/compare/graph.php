<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Compare charts for indicator: <?php echo $type; ?></h3>
    <div>Price chart for <span id='title1'></span><div>
    <div id='price-chart'></div>
    <div>Comparison chart of indicator <?php echo $type ;?> between <span id='title2'></span> and <span id='title3'></span><div>
    <div id='compare-chart'></div>
<h3>Data set</h3>
<table id='data-set'>
    <thead>
        <tr>
            <td>Datetime</td>
            <td>Price instrument</td>
            <td>Price</td>
            <td>Instrument</td>
            <td>Value</td>
        </tr>
    </thead>
    <tbody>
    <?php foreach($comparison as $comp): ?>
        <tr>
            <td><?php echo $comp['datetime']; ?></td>
            <td><?php echo $comp['priceInstrument']; ?></td>
            <td><?php echo $comp['price']; ?></td>
            <td><?php echo $comp['instrument']; ?></td>
            <td><?php echo $comp['actual']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include(__DIR__ . '/../common/foot.php'); ?>
