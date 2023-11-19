<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;

final class DataProvider implements DataProviderInterface
{
    private ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @throws JsonException
     */
    public function getData(ServerRequestInterface $request): array
    {
        $data = [];
        $bodyJsonDevices = [];

        $context = stream_context_create(['http' => ['header' => [sprintf('Authorization: Bearer %s', $this->config->getApiToken())]]]);

        $devicesWithReadingTemp = [];

        $devicesUrl = 'https://api.smartthings.com/v1/devices/';
        $rawDevicesData = file_get_contents($devicesUrl, false, $context);
        $jsonDevices = json_decode($rawDevicesData, true, 512, \JSON_THROW_ON_ERROR);
        if (!empty($jsonDevices['items']) && is_array($jsonDevices['items'])) {
            foreach ($jsonDevices['items'] as $device) {
                $deviceSupportsReadingTemp = false;
                if (is_array($device) && !empty($device['name']) && is_string($device['name']) && !empty($device['deviceId']) && is_string($device['deviceId']) && !empty($device['components'][0]['capabilities']) && is_array($device['components'][0]['capabilities'])) {
                    foreach ($device['components'][0]['capabilities'] as $capability) {
                        if (is_array($capability) && !empty($capability['id']) && 'temperatureMeasurement' === $capability['id']) {
                            $deviceSupportsReadingTemp = true;
                            break;
                        }
                    }
                    if ($deviceSupportsReadingTemp) {
                        $devicesWithReadingTemp[$device['name']] = $device['deviceId'];
                    }
                }
            }
        }

        $totalForAverage = 0;
        $totalDevicesAveraged = 0;
        $latestNonStaleTimestamp = null;
        foreach ($devicesWithReadingTemp as $deviceName => $deviceId) {
            $deviceUrl = sprintf('https://api.smartthings.com/v1/devices/%s/status', $deviceId);
            $rawDeviceData = file_get_contents($deviceUrl, false, $context);
            $jsonDevice = json_decode($rawDeviceData, true, 512, \JSON_THROW_ON_ERROR);
            if (!empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value']) && !empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp'])) {
                $temp = (float) $jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value'];
                $timestamp = strtotime($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp']);
                $stale = $timestamp < time() - (24 * 60 * 60);
                if (!$stale) {
                    $totalForAverage += $temp;
                    ++$totalDevicesAveraged;
                    if (null === $latestNonStaleTimestamp || $timestamp < $latestNonStaleTimestamp) {
                        $latestNonStaleTimestamp = $timestamp;
                    }
                }
                $bodyJsonDevices[] = [
                    'name' => $deviceName,
                    'temp' => $temp,
                    'timestamp' => $timestamp,
                    'stale' => $stale,
                ];
            }
        }
        usort(
            $bodyJsonDevices,
            static fn ($a, $b) => strcmp($a['name'], $b['name'])
        );

        $data['devices'] = $bodyJsonDevices;
        if ($totalDevicesAveraged > 0) {
            $data['averageTempDegrees'] = $totalForAverage / $totalDevicesAveraged;
            $data['averageTempTimestamp'] = $latestNonStaleTimestamp;
        }

        return $data;
    }
}
