<?php

namespace App\Services\Scoring;

interface CategoryScorer
{
    /**
     * Category identifier.
     */
    public function key(): string;

    /**
     * Human-readable label.
     */
    public function label(): string;

    /**
     * Weight (0.0 - 1.0) in the total score.
     */
    public function weight(): float;

    /**
     * Calculate score (0-100) from the given data.
     */
    public function score(array $profileData, array $metrics, array $posts): int;
}
