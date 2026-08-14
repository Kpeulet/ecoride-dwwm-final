<?php
// On génère un hash très simple et compatible avec password_verify()
echo password_hash("admin123", PASSWORD_BCRYPT);
?>