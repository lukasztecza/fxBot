<h3>Add form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post">
    <input type="button" value="Add input" />
    <div>
        <input type="text" name="items[]" /><br />
    </div>
    <input type="submit" />
</form>
<script src="/assets/js/core.js<?php echo $assetsVersioning; ?>"></script>
