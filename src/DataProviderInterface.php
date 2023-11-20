<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\DataProviderInterface as BaseDataProviderInterface;

interface DataProviderInterface extends BaseDataProviderInterface
{
    public const STALE_TEMPERATURE_THRESHOLD = 24 * 60 * 60;
}
