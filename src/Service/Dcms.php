<?php

namespace SNOWGIRL_CORE\Service;

abstract class Dcms extends Cache
{
    public const CLEANING_MODE_MATCHING_ANY_TAG = 0;
    public const CLEANING_MODE_ALL = 1;
    public const CLEANING_MODE_OLD = 2;
}