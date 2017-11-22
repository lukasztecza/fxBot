<p>Pages:</p>
<ul>
    <?php for($i = 1; $i <= $pages; $i++): ?>
        <li>
            <?php if($i === (int)$page): ?>
                <?php echo $i ?>
            <?php else: ?>
                <a href="<?php echo $prefix . $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        </li>
    <?php endfor; ?>
</ul>
