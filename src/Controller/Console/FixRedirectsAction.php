<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\Console\ConsoleApp as App;
use SNOWGIRL_CORE\Helper\Arrays;

class FixRedirectsAction
{
    use PrepareServicesTrait;

    public function __invoke(App $app)
    {
        $this->prepareServices($app);

        $manager = $app->managers->redirects;

        //#1 delete duplicates
        $app->request->setParams([
            'param_1' => $manager->getEntity()->getTable(),
            'param_2' => 'uri_from'
        ]);

        (new DeleteDuplicatesAction)($app);

        //#2 outdated paths
        $aff = 0;

        for ($i = 0; $i < 3; $i++) {
            $fromToTo = Arrays::mapByKeyValueMaker($manager->getArrays(), function ($k, $row) {
                return [$row['uri_from'], $row['uri_to']];
            });

            $db = $app->container->db;

            foreach ($fromToTo as $from => $to) {
                if (isset($fromToTo[$to])) {
                    $newTo = $fromToTo[$to];
                    $db->makeTransaction(function () use ($manager, $from, $to, $newTo) {
                        $manager->updateMany(['uri_to' => $newTo], ['uri_from' => $from]);
                        $manager->deleteMany(['uri_from' => $to]);
                    });
                    unset($fromToTo[$to]);
                    $aff++;
                }
            }
        }

        $app->response->setBody('DONE: ' . $aff);
    }
}