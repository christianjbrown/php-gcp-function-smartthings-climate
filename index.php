<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\CloudFunction\CloudFunction;
use ChristianBrown\CloudFunction\FunctionConfigTransformer;
use ChristianBrown\JsonApiClient\RequestSender;
use ChristianBrown\SmartThings\Api\DeviceApi;
use ChristianBrown\SmartThings\Api\DeviceStatusApi;
use ChristianBrown\SmartThings\Transformer\BadResponseTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceComponentCapabilitiesTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceComponentCapabilityTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceComponentsTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceComponentTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceStatusTemperatureMeasurementTemperatureTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceStatusTemperatureMeasurementTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceStatusTransformer;
use ChristianBrown\SmartThings\Transformer\DevicesTransformer;
use ChristianBrown\SmartThings\Transformer\DeviceTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);

    $transformer = new BadResponseTransformer();
    $requestSender = new RequestSender($transformer);

    $deviceComponentCapabilityTransformer = new DeviceComponentCapabilityTransformer();
    $deviceComponentCapabilitiesTransformer = new DeviceComponentCapabilitiesTransformer($deviceComponentCapabilityTransformer);
    $deviceComponentTransformer = new DeviceComponentTransformer($deviceComponentCapabilitiesTransformer);
    $deviceComponentsTransformer = new DeviceComponentsTransformer($deviceComponentTransformer);
    $deviceTransformer = new DeviceTransformer($deviceComponentsTransformer);
    $devicesTransformer = new DevicesTransformer($deviceTransformer);
    $devicesApi = new DeviceApi($requestSender, $devicesTransformer, $config->getApiToken());

    $deviceStatusTemperatureMeasurementTemperatureTransformer = new DeviceStatusTemperatureMeasurementTemperatureTransformer();
    $deviceStatusTemperatureMeasurementTransformer = new DeviceStatusTemperatureMeasurementTransformer($deviceStatusTemperatureMeasurementTemperatureTransformer);
    $deviceStatusTransformer = new DeviceStatusTransformer($deviceStatusTemperatureMeasurementTransformer);
    $devicesStatusApi = new DeviceStatusApi($requestSender, $deviceStatusTransformer, $config->getApiToken());

    $deviceTemperatureOutputTransformer = new DeviceTemperatureOutputTransformer();
    $outputTransformer = new OutputTransformer($deviceTemperatureOutputTransformer);

    $dataProvider = new DataProvider($devicesApi, $devicesStatusApi, $outputTransformer);
    $cloudFunction = new CloudFunction($dataProvider, $config->getFunctionConfig());
    $response = $cloudFunction->run($request);

    return $response;
}

