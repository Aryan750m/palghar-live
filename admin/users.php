<?php
// User Management Placeholder: admin/users.php
// Display gate placeholder for Phase 1 - User profile controls are scheduled for Phase 3

require_once 'header.php';

// Verification check: Only Admin role can access
if ($adminRole !== 'admin') {
    echo '<div class="stat-card" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:15px; border-radius:4px; margin-bottom:20px;">
        <span style="color:#991b1b; font-weight:bold;">✗ Access Gate Error:</span>
        <p style="color:#991b1b; margin:5px 0 0 0; font-size:0.9rem;">You do not possess the required administrator privileges to view this section.</p>
    </div>';
    require_once 'footer.php';
    exit;
}
?>
<div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; text-align: center; margin-bottom: 25px;">
    <i class="fas fa-users-cog fa-3x" style="color: var(--primary); margin-bottom: 20px;"></i>
    <h3 style="font-family: var(--font-heading);">User Profile & Permissions Manager</h3>
    <p style="color: var(--text-muted); margin: 15px 0; max-width:600px; margin-left:auto; margin-right:auto; line-height:1.6;">
        The User Management controls are currently deactivated in **Phase 1** database migration and will be fully introduced in **Phase 3**.
    </p>
    <div style="background-color: var(--bg-main); border: 1px solid var(--border-color); padding: 15px; border-radius: 4px; display: inline-block; text-align: left; font-size: 0.88rem; line-height: 1.5; color: var(--text-muted);">
        <strong>Phase 1 Initial Accounts:</strong><br>
        1. <code>admin</code> (Role: administrator) | Password: <code>admin123</code><br>
        2. <code>usera</code> (Role: editor, Assigned: Sports, Business) | Password: <code>user123</code><br>
        3. <code>userb</code> (Role: editor, Assigned: Palghar Local, Art & Culture) | Password: <code>user123</code>
    </div>
</div>
<?php
require_once 'footer.php';
?>
