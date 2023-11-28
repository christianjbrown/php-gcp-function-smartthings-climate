<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps\Tests;

use ChristianBrown\GetSmartHomeTemps\DeviceTemperatureInterface;
use ChristianBrown\GetSmartHomeTemps\DeviceTemperatureOutputTransformerInterface;
use ChristianBrown\GetSmartHomeTemps\OutputTransformer;
use ChristianBrown\GetSmartHomeTemps\OutputTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputTransformer::class)]
final class OutputTransformerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test(): void
    {
        $deviceTemperature1 = $this->createDeviceTemperature('test-device-1', 42.2, 29000, true);
        $deviceTemperature2 = $this->createDeviceTemperature('test-device-2', 52.2, 39000, false);
        $deviceTemperature3 = $this->createDeviceTemperature('test-device-3', 62.2, 49000, false);
        $deviceTemperature4 = $this->createDeviceTemperature('test-device-4', 72.2, 59000, true);

        $deviceTemperatureOutputTransformer = $this->createMock(DeviceTemperatureOutputTransformerInterface::class);
        $deviceTemperatureOutputTransformer->method('transform')
            ->willReturnMap(
                [
                    [$deviceTemperature1, ['test-device-1']],
                    [$deviceTemperature2, ['test-device-2']],
                    [$deviceTemperature3, ['test-device-3']],
                    [$deviceTemperature4, ['test-device-4']],
                ]
            );

        $transformer = new OutputTransformer($deviceTemperatureOutputTransformer);

        $expected = [
            OutputTransformerInterface::KEY_DEVICES => [
                ['test-device-1'],
                ['test-device-2'],
                ['test-device-3'],
                ['test-device-4'],
            ],
            OutputTransformerInterface::KEY_AVERAGE_TEMPERATURE_VALUE => 57.2,
            OutputTransformerInterface::KEY_AVERAGE_TEMPERATURE_TIMESTAMP => 39000,
        ];

        $actual = $transformer->transform([$deviceTemperature1, $deviceTemperature2, $deviceTemperature3, $deviceTemperature4]);

        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createDeviceTemperature(string $label, float $temperature, int $timestamp, bool $isStale): DeviceTemperatureInterface
    {
        $deviceTemperature = $this->createMock(DeviceTemperatureInterface::class);
        $deviceTemperature->method('getLabel')
            ->willReturn($label);
        $deviceTemperature->method('getTemperature')
            ->willReturn($temperature);
        $deviceTemperature->method('getTimestamp')
            ->willReturn($timestamp);
        $deviceTemperature->method('isStale')
            ->willReturn($isStale);

        return $deviceTemperature;
    }
}
