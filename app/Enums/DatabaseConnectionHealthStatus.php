<?php

namespace App\Enums;

enum DatabaseConnectionHealthStatus: string
{
    case UNKNOWN = 'UNKNOWN';
    case HEALTHY = 'HEALTHY';
    case UNHEALTHY = 'UNHEALTHY';
}
