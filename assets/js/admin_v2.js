// Admin Portal Helper Scripts (Phase 1)
// Standard PHP forms handle news publish, metadata edits, comment lists and slide reordering.
// Client script acts as a helper for local preset selections and notification overlays.

document.addEventListener('DOMContentLoaded', () => {
    // 1. Alert Notifications dismiss toast check
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status) {
        let msg = '';
        let isSuccess = true;
        
        switch (status) {
            case 'update_success':
                msg = 'Article updated successfully!';
                break;
            case 'create_success':
                msg = 'New article published successfully!';
                break;
            case 'delete_success':
                msg = 'Article deleted successfully.';
                break;
            case 'meta_success':
                msg = 'Section details saved successfully!';
                break;
            case 'image_added':
                msg = 'Image slide added to section!';
                break;
            case 'image_replaced':
                msg = 'Image slide replaced successfully!';
                break;
            case 'image_deleted':
                msg = 'Image slide removed successfully.';
                break;
            case 'reorder_success':
                msg = 'Slides sorting order updated!';
                break;
            case 'permission_denied':
                msg = 'Access Denied: You do not possess authorized privileges.';
                isSuccess = false;
                break;
            case 'invalid_file':
                msg = 'Invalid file uploaded. Limit size to 5MB (JPG/PNG/WEBP).';
                isSuccess = false;
                break;
            case 'missing_fields':
                msg = 'Please fill out all required form fields.';
                isSuccess = false;
                break;
            case 'save_error':
            case 'delete_error':
            case 'action_error':
                msg = 'Database transaction failed.';
                isSuccess = false;
                break;
        }
        
        if (msg) {
            // Delay slightly to allow page load to settle
            setTimeout(() => {
                showToast(msg, isSuccess);
            }, 100);
        }
    }
});
