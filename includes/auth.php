<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirectIfNotLoggedIn($requiredRole = null) {
    if (!isset($_SESSION['user'])) {
        header('Location: ../auth/login.php');
        exit;
    }

    // If a specific role is required and doesn't match
    if ($requiredRole && $_SESSION['user']['role'] !== $requiredRole) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}
