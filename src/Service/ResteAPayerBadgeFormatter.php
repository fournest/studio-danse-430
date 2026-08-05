<?php

namespace App\Service;

/**
 * Badge HTML « Reste à payer » pour les vues EasyAdmin.
 */
final class ResteAPayerBadgeFormatter
{
    public function html(float $reste): string
    {
        $formatted = number_format($reste, 2, ',', ' ');

        if ($reste <= 0.001) {
            return sprintf(
                '<span class="badge bg-success" title="Soldé">%s €</span>',
                $formatted
            );
        }

        $class = $reste >= 50.0 ? 'bg-danger' : 'bg-warning text-dark';

        return sprintf(
            '<span class="badge %s" title="Reste à payer">%s €</span>',
            $class,
            $formatted
        );
    }

    public function htmlWithLabel(float $reste, string $prefix = 'Reste :'): string
    {
        return sprintf('%s %s', htmlspecialchars($prefix, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'), $this->html($reste));
    }
}
