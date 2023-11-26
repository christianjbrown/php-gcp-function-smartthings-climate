<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

final class DeviceTemperatureOutputTransformer implements DeviceTemperatureOutputTransformerInterface
{
    public function transform(DeviceTemperatureInterface $deviceTemperature): array
    {
        $data = [
            self::KEY_NAME => $deviceTemperature->getName(),
            self::KEY_TEMPERATURE => $deviceTemperature->getTemperature(),
            self::KEY_TIMESTAMP => $deviceTemperature->getTimestamp(),
            self::KEY_STALE => $deviceTemperature->isStale(),
        ];

        return $data;
    }
}
