<?php

declare(strict_types=1);

final class DeviceTemperature implements DeviceTemperatureInterface
{
    private string $name;
    private bool $stale;
    private float $temperature;
    private int $timestamp;

    public function __construct(string $name, float $temperature, int $timestamp, bool $stale)
    {
        $this->name = $name;
        $this->temperature = $temperature;
        $this->timestamp = $timestamp;
        $this->stale = $stale;
    }

    public function getName(): string
    {
        return $this->name;
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
