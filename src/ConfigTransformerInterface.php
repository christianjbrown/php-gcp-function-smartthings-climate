<?php

declare(strict_types=1);

interface ConfigTransformerInterface
{
    public const ENV_API_TOKEN = 'SMARTTHINGS_API_TOKEN';

    public function transform(array $env): ConfigInterface;
}
