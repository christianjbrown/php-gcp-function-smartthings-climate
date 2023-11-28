<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

final class DeviceTemperature implements DeviceTemperatureInterface
{
    private string $label;
    private bool $stale;
    private float $temperature;
    private int $timestamp;

    public function __construct(string $label, float $temperature, int $timestamp, bool $stale)
    {
        $this->label = $label;
        $this->temperature = $temperature;
        $this->timestamp = $timestamp;
        $this->stale = $stale;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function isStale(): bool
    {
        return $this->stale;
    }
}
