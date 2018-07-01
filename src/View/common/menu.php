<ul>
    <li><a href="/">Home page</a></li>
    <li><a href="/stats">Stats page</a></li>
    <li><a href="/compare">Compare</a></li>
    <?php if ($loggedIn): ?>
        <li><a href="/logout">Logout</a></li>
    <?php else: ?>
        <li><a href="/login">Login</a></li>
    <?php endif; ?>
</ul>
