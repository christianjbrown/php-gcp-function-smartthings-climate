<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfigInterface;

interface ConfigInterface
{
    public function getApiToken(): string;

    public function getFunctionConfig(): FunctionConfigInterface;
}
