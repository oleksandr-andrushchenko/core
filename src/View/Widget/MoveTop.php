<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;

class MoveTop extends Widget
{
    public function getCoreDomClass(): string
    {
        return 'widget-move-top';
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('a', [
            'class' => $this->getDomClass(),
            'id' => $this->getDomId(),
            'href' => '#top'
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return $this->makeNode('span', ['class' => 'glyphicon glyphicon-chevron-up']);
    }

    protected function addScripts(): Widget
    {
        $domId = $this->getDomId();

        $this->addCss("
            #{$domId}.affix-top{position:absolute;bottom:-82px;right:20px}
            #{$domId}.affix{position:fixed;bottom:30px;right:30px}
            #{$domId} .glyphicon{color:#fff}
        ", true);

        $this->addJsScript('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');

        $this->addJs("
            if (($(window).height() + 100) < $(document).height()) {
                $('#{$domId}').removeClass('hidden').affix({
                    offset: {top:100}
                });
            }
            
            $('#{$domId}').on('click', function() {
                $('html, body').animate({scrollTop: 0}, 'slow');
                $(this).blur();
                return false;
            });
        ", true);

        return parent::addScripts();
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('hidden-xs hidden-mb btn btn-primary');
        return parent::stringifyPrepare();
    }
}