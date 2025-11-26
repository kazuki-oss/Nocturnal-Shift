<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ago', [$this, 'formatAgo']),
        ];
    }

    public function formatAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) {
            return $diff->y . '年前';
        }
        if ($diff->m > 0) {
            return $diff->m . 'ヶ月前';
        }
        if ($diff->d > 0) {
            return $diff->d . '日前';
        }
        if ($diff->h > 0) {
            return $diff->h . '時間前';
        }
        if ($diff->i > 0) {
            return $diff->i . '分前';
        }

        return 'たった今';
    }
}
