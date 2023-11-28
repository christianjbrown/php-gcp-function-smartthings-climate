<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps\Tests;

use ChristianBrown\GetSmartHomeTemps\DeviceTemperatureInterface;
use ChristianBrown\GetSmartHomeTemps\DeviceTemperatureOutputTransformer;
use ChristianBrown\GetSmartHomeTemps\DeviceTemperatureOutputTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeviceTemperatureOutputTransformer::class)]
final class DeviceTemperatureOutputTransformerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test(): void
    {
        $transformer = new DeviceTemperatureOutputTransformer();
        $deviceTemperature = $this->createMock(DeviceTemperatureInterface::class);
        $deviceTemperature->method('getLabel')
            ->willReturn('test-label');
        $deviceTemperature->method('getTemperature')
            ->willReturn(42.2);
        $deviceTemperature->method('getTimestamp')
            ->willReturn(29000);
        $deviceTemperature->method('isStale')
            ->willReturn(true);

        $expected = [
            DeviceTemperatureOutputTransformerInterface::KEY_LABEL => 'test-label',
            DeviceTemperatureOutputTransformerInterface::KEY_TEMPERATURE => 42.2,
            DeviceTemperatureOutputTransformerInterface::KEY_TIMESTAMP => 29000,
            DeviceTemperatureOutputTransformerInterface::KEY_STALE => true,
        ];

        $actual = $transformer->transform($deviceTemperature);

        self::assertSame($expected, $actual);
    }
}
