<?php
session_start();

function requireRole($roles = []) {
    if (!isset($_SESSION['OfficerID'])) {
        header("Location: login.php");
        exit;
    }

    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: no_permission.php");
        exit;
    }
}