<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

interface DeviceTemperatureOutputTransformerInterface
{
    public const KEY_LABEL = 'name';
    public const KEY_STALE = 'stale';
    public const KEY_TEMPERATURE = 'temp';
    public const KEY_TIMESTAMP = 'timestamp';

    public function transform(DeviceTemperatureInterface $deviceTemperature): array;
}
