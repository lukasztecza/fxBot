<p>Edit form</p>
<p class="error"><?php echo $error; ?></p>
<form method="post">
<input type="text" name="name" value="<?php echo $item['name'] ?? null; ?>" />
<input type="submit" />
</form>
