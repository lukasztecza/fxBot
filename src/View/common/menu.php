<ul>
    <li><a href="/">Home page</a></li>
    <li><a href="/file/add">Add file</a></li>
    <li><a href="/file">Files list</a></li>
    <?php if ($loggedIn): ?>
        <li><a href="/logout">Logout</a></li>
    <?php else: ?>
        <li><a href="/login">Login</a></li>
    <?php endif; ?>
</ul>
