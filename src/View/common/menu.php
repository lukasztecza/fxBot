<ul>
    <li><a href="/">Home page</a></li>
    <li><a href="/files/add">Add file</a></li>
    <li><a href="/files">Files list</a></li>
    <?php if ($loggedIn): ?>
        <li><a href="/logout">Logout</a></li>
    <?php else: ?>
        <li><a href="/login">Login</a></li>
    <?php endif; ?>
</ul>
