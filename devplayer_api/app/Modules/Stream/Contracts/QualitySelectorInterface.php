<?php

namespace App\Modules\Stream\Contracts;

use App\Modules\Stream\Enums\StreamQuality;

interface QualitySelectorInterface
{
    /**
     * Select optimal quality based on network conditions
     *
     * @param array $availableQualities
     * @param array $networkMetrics
     * @return StreamQuality
     */
    public function selectQuality(array $availableQualities, array $networkMetrics): StreamQuality;

    /**
     * Calculate if quality should be upgraded
     *
     * @param StreamQuality $currentQuality
     * @param float $throughput
     * @param int $bufferHealth
     * @return bool
     */
    public function shouldUpgrade(StreamQuality $currentQuality, float $throughput, int $bufferHealth): bool;

    /**
     * Calculate if quality should be downgraded
     *
     * @param StreamQuality $currentQuality
     * @param float $throughput
     * @param int $bufferHealth
     * @return bool
     */
    public function shouldDowngrade(StreamQuality $currentQuality, float $throughput, int $bufferHealth): bool;

    /**
     * Get next quality level (upgrade)
     *
     * @param StreamQuality $current
     * @param array $available
     * @return StreamQuality|null
     */
    public function getNextQuality(StreamQuality $current, array $available): ?StreamQuality;

    /**
     * Get previous quality level (downgrade)
     *
     * @param StreamQuality $current
     * @param array $available
     * @return StreamQuality|null
     */
    public function getPreviousQuality(StreamQuality $current, array $available): ?StreamQuality;

    /**
     * Calculate weighted average throughput (EWMA)
     *
     * @param float $newSample
     * @param float $currentAverage
     * @param float $weight
     * @return float
     */
    public function calculateEWMA(float $newSample, float $currentAverage, float $weight = 0.3): float;

    /**
     * Get optimal starting quality
     *
     * @param float $initialBandwidth
     * @return StreamQuality
     */
    public function getStartingQuality(float $initialBandwidth): StreamQuality;
}
