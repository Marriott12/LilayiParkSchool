<?php
/**
 * Logout Page
 */
session_start();
require_once __DIR__ . '/../../includes/Auth.php';

Auth::logout();
header('Location: /modules/auth/login.php');
exit;
