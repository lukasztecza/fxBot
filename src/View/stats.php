<?php include('common/head.php'); ?>
<h3>Stats</h3>
<?php $prefix = '/stats/'; include(__DIR__ . '/common/paginator.php'); ?>
<table>
    <thead>
        <tr>
            <td>Id</td>
            <td>Account</td>
            <td>Instrument</td>
            <td>Units</td>
            <td>Price</td>
            <td>Take Profit</td>
            <td>Stop Loss</td>
            <td>Balance</td>
            <td>Date</td>
        </tr>
    </thead>
    <tbody>
    <?php foreach($trades as $trade): ?>
        <tr>
            <td><?php echo $trade['id']; ?></td>
            <td><?php echo $trade['account']; ?></td>
            <td><?php echo $trade['instrument']; ?></td>
            <td><?php echo $trade['units']; ?></td>
            <td><?php echo $trade['price']; ?></td>
            <td><?php echo $trade['takeProfit']; ?></td>
            <td><?php echo $trade['stopLoss']; ?></td>
            <td><?php echo $trade['balance']; ?></td>
            <td><?php echo $trade['datetime']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include('common/foot.php'); ?>
