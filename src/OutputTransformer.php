<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

final class OutputTransformer implements OutputTransformerInterface
{
    private DeviceTemperatureOutputTransformerInterface $deviceTemperatureOutputTransformer;

    public function __construct(DeviceTemperatureOutputTransformerInterface $deviceTemperatureOutputTransformer)
    {
        $this->deviceTemperatureOutputTransformer = $deviceTemperatureOutputTransformer;
    }

    /**
     * @param DeviceTemperatureInterface[] $deviceTemperatures
     *
     * @return mixed[]
     */
    public function transform(array $deviceTemperatures): array
    {
        usort(
            $deviceTemperatures,
            static fn (DeviceTemperatureInterface $a, DeviceTemperatureInterface $b) => strcmp($a->getLabel(), $b->getLabel())
        );

        $devicesData = [];

        $totalForAverage = 0;
        $totalDevicesAveraged = 0;
        $latestNonStaleTimestamp = null;
        foreach ($deviceTemperatures as $deviceTemperature) {
            $devicesData[] = $this->deviceTemperatureOutputTransformer->transform($deviceTemperature);
            if (!$deviceTemperature->isStale()) {
                $totalForAverage += $deviceTemperature->getTemperature();
                ++$totalDevicesAveraged;
                $timestamp = $deviceTemperature->getTimestamp();
                if (null === $latestNonStaleTimestamp || $timestamp < $latestNonStaleTimestamp) {
                    $latestNonStaleTimestamp = $timestamp;
                }
            }
        }

        $data = [
            self::KEY_DEVICES => $devicesData,
        ];

        if ($totalDevicesAveraged > 0) {
            $data[self::KEY_AVERAGE_TEMPERATURE_VALUE] = $totalForAverage / $totalDevicesAveraged;
            $data[self::KEY_AVERAGE_TEMPERATURE_TIMESTAMP] = $latestNonStaleTimestamp;
        }

        return $data;
    }
}
