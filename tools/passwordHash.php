<?php
echo 'Please provide password:';
system('stty -echo');
$password = rtrim(fgets(STDIN), PHP_EOL);
system('stty echo');

echo 'Password hash:' . PHP_EOL;
echo password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo PHP_EOL;
