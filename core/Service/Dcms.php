<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/9/17
 * Time: 4:36 PM
 */

namespace SNOWGIRL_CORE\Service;

/**
 * Class Dcms
 * @package SNOWGIRL_CORE\Service
 */
abstract class Dcms extends Cache
{
    public const CLEANING_MODE_MATCHING_ANY_TAG = 0;
    public const CLEANING_MODE_ALL = 1;
    public const CLEANING_MODE_OLD = 2;
}