<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

function run(ServerRequestInterface $request): ResponseInterface
{
    $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Content-Type' => 'application/json; charset=utf-8',
    ];
    $bodyJson = [
        'data' => [],
        'success' => false,
        'timestamp' => time(),
        'version' => (int)getenv('K_REVISION'),
    ];

    try {
        $bodyJsonDevices = [];

        if (empty(getenv('SMARTTHINGS_TOKEN') || !is_string(getenv('SMARTTHINGS_TOKEN')))) {
            throw new RuntimeException('Missing security token');
        }
        $token = getenv('SMARTTHINGS_TOKEN');
        $context = stream_context_create(['http' => ['header' => [sprintf('Authorization: Bearer %s', $token)]]]);

        $devicesWithReadingTemp = [];

        $devicesUrl = 'https://api.smartthings.com/v1/devices/';
        $rawDevicesData = file_get_contents($devicesUrl, false, $context);
        $jsonDevices = json_decode($rawDevicesData, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($jsonDevices['items']) && is_array($jsonDevices['items'])) {
            foreach ($jsonDevices['items'] as $device) {
                $deviceSupportsReadingTemp = false;
                if (is_array($device) && !empty($device['name']) && is_string($device['name']) && !empty($device['deviceId']) && is_string($device['deviceId'])  && !empty($device['components'][0]['capabilities']) && is_array($device['components'][0]['capabilities'])) {
                    foreach ($device['components'][0]['capabilities'] as $capability) {
                        if (is_array($capability) && !empty($capability['id']) && $capability['id'] === 'temperatureMeasurement') {
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
            $jsonDevice = json_decode($rawDeviceData, true, 512, JSON_THROW_ON_ERROR);
            if (!empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value']) && !empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp'])) {
                $temp = (float)$jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value'];
                $timestamp = strtotime($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp']);
                $stale = $timestamp < time() - (24 * 60 * 60);
                if (!$stale) {
                    $totalForAverage += $temp;
                    $totalDevicesAveraged++;
                    if ($latestNonStaleTimestamp === null || $timestamp < $latestNonStaleTimestamp) {
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
            static function($a, $b) {
                return strcmp($a['name'], $b['name']);
            }
        );

        $bodyJson['data']['devices'] = $bodyJsonDevices;
        if ($totalDevicesAveraged > 0) {
            $bodyJson['data']['averageTempDegrees'] = $totalForAverage / $totalDevicesAveraged;
            $bodyJson['data']['averageTempTimestamp'] = $latestNonStaleTimestamp;
        }
        $bodyJson['success'] = true;
        ksort($bodyJson);

        $headers['Surrogate-Control'] = 'max-age=180';
        $headers['Cache-Control'] = 's-maxage=180, max-age=0';
        $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);

        $response = new Response(200, $headers, $body);
    } catch (Throwable $e) {
        $bodyJson['error'] = 'Could not reach Samsung SmartThings API to get the latest device data';
        $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);
        $response = new Response(500, $headers, $body);
    }

    return $response;
}

