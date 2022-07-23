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

    try {
//        $timeLondon = new DateTimeImmutable('now', new DateTimeZone('Europe/London'));
        $bodyJson = [
            'version' => getenv('K_REVISION'),
            'timestamp' => time(),
//            'time_london' => $timeLondon->format('H:i (g:ia)'),
            'devices' => [],
        ];
        if (!empty(getenv('SMARTTHINGS_TOKEN') && is_string(getenv('SMARTTHINGS_TOKEN')))) {
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

            foreach ($devicesWithReadingTemp as $deviceName => $deviceId) {
                $deviceUrl = sprintf('https://api.smartthings.com/v1/devices/%s/status', $deviceId);
                $rawDeviceData = file_get_contents($deviceUrl, false, $context);
                $jsonDevice = json_decode($rawDeviceData, true, 512, JSON_THROW_ON_ERROR);

                if (!empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value']) && !empty($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp'])) {
                    $temp = (float)$jsonDevice['components']['main']['temperatureMeasurement']['temperature']['value'];
                    $timestamp = strtotime($jsonDevice['components']['main']['temperatureMeasurement']['temperature']['timestamp']);
                    $bodyJson['devices'][] = [
                        'name' => $deviceName,
                        'temp' => $temp,
                        'timestamp' => $timestamp,
                    ];
                }
            }
        }
        $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);
        $response = new Response(200, $headers, $body);
    } catch (Throwable $e) {
        $response = new Response(500, $headers, 'An error occurred :(');
    }

    return $response;
}
