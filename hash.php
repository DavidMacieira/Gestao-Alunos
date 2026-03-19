<?php
$password = 'admin123'; // Muda esta password para a que quiseres

echo password_hash($password, PASSWORD_DEFAULT);
?>