<?php
function check_login() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /PSDP/auth/login.php'); // Sesuaikan path jika perlu
        exit;
    }
}

function get_user_role() {
    return $_SESSION['role'] ?? 'guest';
}

function has_access($required_roles) {
    $current_role = get_user_role();
    return in_array($current_role, $required_roles);
}
?>