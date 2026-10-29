<?php
$password = "sabri87";
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo $hash;
