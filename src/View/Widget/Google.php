<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;

class Google extends Widget
{
    protected $tagId;

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'tagId' => $this->app->config('keys.google_tag_id', false),
        ]);
    }

    protected function getNode(): ?Node
    {
        return null;
    }

    protected function getInner(string $template = null): ?string
    {
        return null;
    }

    public function isOk(): bool
    {
        return !!$this->tagId;
    }
}