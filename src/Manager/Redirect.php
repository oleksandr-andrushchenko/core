<?php

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\Redirect as RedirectEntity;

class Redirect extends Manager
{
    protected function onInserted(Entity $entity)
    {
        /** @var RedirectEntity $entity */

        $output = parent::onInserted($entity);

        $output = $output && $this->updateMany(['uri_to' => $entity->getUriTo()], [
                'uri_from' => $entity->getUriFrom()
            ], true);

        return $output;
    }

    /**
     * @param $uri
     *
     * @return array|null
     */
    public function getByUriFrom($uri)
    {
        $tmp = $this->clear()
            ->setColumns(['uri_from', 'uri_to'])
            ->setWhere(['uri_from' => $uri])
            ->getArrays();

        if (is_array($uri)) {
            $output = [];

            foreach ($tmp as $item) {
                $output[$item['uri_from']] = $item['uri_to'];
            }

            return $output;
        }

        if (is_array($tmp) && isset($tmp[0])) {
            return $tmp[0]['uri_to'];
        }

        return null;
    }
}