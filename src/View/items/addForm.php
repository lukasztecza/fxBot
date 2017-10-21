<h3>Add form</h3>
<p><?php echo $error; ?></p>
<form method="post">
    <div>
        <input type="text" name="items[]" />
    </div>
    <input type="submit" />
    <input type="button" value="Add input" />
</form>
<script>
(function() {
    var appendInput = function appendInput() {
        var input = document.createElement("input");
        input.type = "text";
        input.name = "items[]";
        document.querySelector("form > div").appendChild(input);
    }
    document.querySelector("input[type=button]").addEventListener("click", appendInput);
})();
</script>
