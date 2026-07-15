<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

final class DeviceTemperatureOutputTransformer implements DeviceTemperatureOutputTransformerInterface
{
    /**
     * @return mixed[]
     */
    public function transform(DeviceTemperatureInterface $deviceTemperature): array
    {
        $data = [
            self::KEY_LABEL => $deviceTemperature->getLabel(),
            self::KEY_TEMPERATURE => $deviceTemperature->getTemperature(),
            self::KEY_TIMESTAMP => $deviceTemperature->getTimestamp(),
            self::KEY_STALE => $deviceTemperature->isStale(),
        ];

        return $data;
    }
}
