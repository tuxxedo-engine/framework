<?php

declare(strict_types=1);

namespace Unit\Collections;

enum StringTestEnum: string
{
    case DK = 'Denmark';
    case FI = 'Finland';
    case IS = 'Iceland';
    case NO = 'Norway';
    case SE = 'Sweden';
}
