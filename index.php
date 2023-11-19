<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\CloudFunction\CloudFunction;
use ChristianBrown\CloudFunction\FunctionConfigTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);

    $dataProvider = new DataProvider($config);
    $cloudFunction = new CloudFunction($dataProvider, $config->getFunctionConfig());
    $response = $cloudFunction->run($request);

    return $response;
}

