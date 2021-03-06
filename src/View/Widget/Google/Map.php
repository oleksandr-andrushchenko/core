<?php

namespace SNOWGIRL_CORE\View\Widget\Google;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Google;

class Map extends Google
{
    protected $key;
    protected $height;
    protected $zoom;
    protected $center;
    protected $marker;

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'key' => $this->app->config('keys.google_api_key', false),
            'center' => $params['center'] ?? ['longitude' => false, 'latitude' => false],
            'zoom' => $params['zoom'] ?? 10,
            'height' => $params['height'] ?? 300,
            'marker' => $params['marker'] ?? true,
            'domId' => 'gmap_canvas'
        ]);
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'id' => $this->getDomId(),
            'style' => 'height:' . $this->height . 'px;width:100%'
        ]);
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addJsScript('https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . $this->key)
            ->addJs("
            function init_map() {
                var map = new google.maps.Map(document.getElementById('" . $this->getDomId() . "'), {
                    zoom: " . $this->zoom . ",
                    center: new google.maps.LatLng(" . $this->center['latitude'] . ", " . $this->center['longitude'] . "),
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });
" . ($this->marker ? "
                new google.maps.Marker({
                    map: map,
                    position: new google.maps.LatLng(" . $this->center['latitude'] . ", " . $this->center['longitude'] . ")
                });
" : "") . "
            }
            google.maps.event.addDomListener(window, 'load', init_map);
        ", true, false, true);
    }

    public function isOk(): bool
    {
        return !!$this->key;
    }
}