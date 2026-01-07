<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout user
Session::destroy();
Utils::redirect(BASE_URL . '/login.php');
