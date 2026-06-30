<?php
// Core Auth Helper: includes/auth.php
// Placeholder for Phase 1 - Session auth gates are deferred to Phase 2

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (mock session key set via AJAX or mock controller)
function isLoggedIn() {
    return isset($_SESSION['palghar_live_admin_session']) && $_SESSION['palghar_live_admin_session'] === true;
}

// Bypassed check for Phase 1
function verifyActionPermission($action, $targetSectionId = null) {
    // In Phase 1, auth is checked client-side using sessionStorage to match the original behavior
    return true;
}
