<?php

// app/Components/Toast.php

namespace App\Components;

class Toast
{
    /**
     * Render the container markup for dynamic toast notifications
     */
    public static function renderContainer(): string
    {
        return '<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>';
    }

    /**
     * Render a server-side static toast if session message is set
     */
    public static function renderStatic(string $message, string $type = 'success'): string
    {
        $icon = ($type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle';
        return sprintf(
            '
            <script nonce="%s">
                document.addEventListener("DOMContentLoaded", () => {
                    if (typeof showToast === "function") {
                        showToast(%s, %s);
                    }
                });
            </script>
        ',
            \App\Middleware\SecurityHeaders::getNonce(),
            json_encode($message),
            json_encode($type)
        );
    }
}
