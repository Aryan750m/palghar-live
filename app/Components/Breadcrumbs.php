<?php

// app/Components/Breadcrumbs.php

namespace App\Components;

class Breadcrumbs
{
    /**
     * Render an accessible breadcrumb trail (WCAG AA compliant)
     *
     * @param array $crumbs Associative array of Name => URL
     */
    public static function render(array $crumbs): string
    {
        $html = '<nav class="breadcrumbs-container" aria-label="Breadcrumb">';
        $html .= '<ol class="breadcrumbs-list">';

        $count = count($crumbs);
        $i = 1;

        foreach ($crumbs as $name => $url) {
            $isLast = ($i === $count);
            $html .= '<li class="breadcrumb-item">';

            if ($isLast) {
                $html .= sprintf('<span class="breadcrumb-current" aria-current="page">%s</span>', htmlspecialchars($name));
            } else {
                $html .= sprintf('<a href="%s" class="breadcrumb-link">%s</a>', htmlspecialchars($url), htmlspecialchars($name));
                $html .= '<span class="breadcrumb-separator" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>';
            }

            $html .= '</li>';
            $i++;
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }
}
