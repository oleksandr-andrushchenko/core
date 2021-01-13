<?php

namespace SNOWGIRL_CORE\Elasticsearch;

class ElasticsearchQuery
{
    public $queryStringQuery;
    public $queryStringFields;
    public $queryStringPrefix;
    public $queryBoolFilter;
    public $sourceIncludes;
    public $sort;
    public $from = 0;
    public $size = 10;
    public $paths;
    public $idsOnly;
    public $idsAsKeys;
    public $log = true;
}