// assets/js/toast.js
// Handles dynamic UI toast alerts

function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-item ${type}`;
    
    let iconClass = 'fa-check-circle';
    if (type === 'error') {
        iconClass = 'fa-exclamation-circle';
    } else if (type === 'info') {
        iconClass = 'fa-info-circle';
    }

    toast.innerHTML = `
        <i class="fas ${iconClass}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    // Trigger visual slide-in
    setTimeout(() => {
        toast.classList.add('show');
    }, 50);

    // Fade-out and delete after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}
