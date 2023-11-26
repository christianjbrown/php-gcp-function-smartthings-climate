<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

interface DeviceTemperatureInterface
{
    public function getName(): string;

    public function getTemperature(): float;

    public function getTimestamp(): int;

    public function isStale(): bool;
}
