<?php

namespace App\Enums;

enum DatabaseActivityStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
