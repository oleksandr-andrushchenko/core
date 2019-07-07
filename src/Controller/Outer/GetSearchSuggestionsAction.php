<?php

namespace SNOWGIRL_CORE\Controller\Outer;

use SNOWGIRL_CORE\App\Web as App;
use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Exception\HTTP\BadRequest;
use SNOWGIRL_CORE\Helper\Data;
use SNOWGIRL_CORE\Manager;

class GetSearchSuggestionsAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        if (!$query = trim($query = $app->request->get('query'))) {
            throw (new BadRequest)->setInvalidParam('query');
        }

        $search = $app->views->searchForm();
        $types = $search->getSuggestionsTypes();

        if ($type = $app->request->get('type')) {
            if (!in_array($type, $types)) {
                throw (new BadRequest)->setInvalidParam('type');
            }
        } else {
            $type = $types[0];
        }

//        $defaultLimit = intdiv(10, count($types));
//        $defaultLimit = 10;
        $defaultLimit = $search->getParam('suggestionsLimit');

        if (!$limit = min(10, (int)$app->request->get('limit', $defaultLimit))) {
            $limit = 10;
        }

        $queries = [];
        $queries[] = $query;

        if (Data::isEnText($query)) {
            $queries[] = Data::keyboardSwitchTo($query, 'ru');
        }

        $output = [];
//        $time = time();

        foreach ($queries as $query) {
            /** @var Manager $manager */
            $manager = $app->managers->$type;

            $display = $manager->findColumns(Entity::SEARCH_DISPLAY)[0];

            $manager->clear()->setLimit($limit);

            foreach (['is_active', 'active'] as $k) {
                if ($manager->getEntity()->hasAttr($k)) {
                    $manager->setWhere([$k => Entity::normalizeBool(true)]);
                }
            }

            foreach ($manager->getObjectsByQuery($query) as $entity) {
                $output[] = [
                    'id' => $entity->getId(),
                    'value' => $value = $entity->get($display),
//                    'tokens' => preg_split("/[\s]+/", $value),
                    'view' => (string)$app->views->entity($entity, 'suggestion'),
                    'link' => $manager->getLink($entity),
                    'type' => $type,
//                    'time' => $time
                ];
            }

            if ($output) {
                break;
            }
        }

        $app->response->setJSON(200, $output);
    }
}