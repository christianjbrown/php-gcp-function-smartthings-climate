<?php

declare(strict_types=1);

namespace ChristianBrown\GetSmartHomeTemps;

interface ConfigTransformerInterface
{
    public const ENV_API_TOKEN = 'SMARTTHINGS_API_TOKEN';

    public function transform(array $env): ConfigInterface;
}
