<?php

namespace App\Enums;

enum DatabaseConnectionFailureType: string
{
    case TIMEOUT = 'TIMEOUT';

    case AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';

    case HOST_UNREACHABLE = 'HOST_UNREACHABLE';

    case DATABASE_UNAVAILABLE = 'DATABASE_UNAVAILABLE';

    case TLS_ERROR = 'TLS_ERROR';

    case READ_ONLY_SETUP_FAILED = 'READ_ONLY_SETUP_FAILED';

    case UNKNOWN = 'UNKNOWN';
}
