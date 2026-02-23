<?php
// Quick local test script to simulate export request
session_start();
$_GET['export'] = 'excel';
include 'fees_paid.php';
