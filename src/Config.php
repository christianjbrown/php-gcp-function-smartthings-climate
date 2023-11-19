<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfigInterface;

final class Config implements ConfigInterface
{
    private string $apiToken;
    private FunctionConfigInterface $functionConfig;

    public function __construct(FunctionConfigInterface $functionConfig, string $apiToken)
    {
        $this->functionConfig = $functionConfig;
        $this->apiToken = $apiToken;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getFunctionConfig(): FunctionConfigInterface
    {
        return $this->functionConfig;
    }
}
