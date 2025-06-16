<?php
$password_plaintext = "karyawan123";
$hashed_password = password_hash($password_plaintext, PASSWORD_DEFAULT);

echo "Password Plaintext: " . htmlspecialchars($password_plaintext) . "<br>";
echo "Hash yang Dihasilkan: " . htmlspecialchars($hashed_password);
?>