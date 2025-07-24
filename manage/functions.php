<?php
// Function to generate a secure token
function generateToken($length = 8) {
    return bin2hex(random_bytes($length));
}
?>