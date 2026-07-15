<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

interface OutputTransformerInterface
{
    public const KEY_AVERAGE_TEMPERATURE_TIMESTAMP = 'averageTempTimestamp';
    public const KEY_AVERAGE_TEMPERATURE_VALUE = 'averageTempDegrees';
    public const KEY_DEVICES = 'devices';

    /**
     * @param DeviceTemperatureInterface[] $deviceTemperatures
     *
     * @return mixed[]
     */
    public function transform(array $deviceTemperatures): array;
}
