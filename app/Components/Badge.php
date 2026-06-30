<?php

// app/Components/Badge.php

namespace App\Components;

use App\Helpers;

class Badge
{
    /**
     * Render a styled category badge UI component
     */
    public static function render(string $catId): string
    {
        $label = Helpers::getCategoryLabel($catId);
        $color = Helpers::getCategoryColor($catId);

        return sprintf(
            '<span class="category-badge" style="background-color: %s;" data-category-id="%s">%s</span>',
            htmlspecialchars($color),
            htmlspecialchars($catId),
            htmlspecialchars($label)
        );
    }
}
