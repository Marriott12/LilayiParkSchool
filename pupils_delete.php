<?php
/**
 * Delete Pupil - Redirects to generic delete handler
 */
$pupilID = $_GET['id'] ?? null;
if ($pupilID) {
    header("Location: delete.php?module=pupils&id=$pupilID");
} else {
    header("Location: pupils_list.php");
}
exit;
