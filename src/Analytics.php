<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Console\ConsoleApp;
use SNOWGIRL_CORE\Entity\Page;
use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Query\Expression;

/**
 * @todo    do not log hits... parse access.log file instead...
 * Class Analytics
 * @package SNOWGIRL_CORE
 */
class Analytics
{
    public const PAGE_HIT = 'hit.page';

    /**
     * @var AbstractApp|HttpApp|ConsoleApp
     */
    protected $app;
    protected $time;
    protected $fileTemplate;

    public function __construct(string $fileTemplate, AbstractApp $app)
    {
        $this->fileTemplate = $fileTemplate;
        $this->app = $app;
        $this->time = $app->request->getServer('REQUEST_TIME');
    }

    public function logPageHit($page)
    {
        $this->logHit(self::PAGE_HIT, $page);
    }

    /**
     * @return bool
     */
    protected function updatePagesRatingsByHits()
    {
        $keyToId = array_map(function (Page $page) {
            return $page->getId();
        }, $this->app->managers->pages->clear()->getObjects('key'));

        $counts = [];

        $isOk = $this->walkFile(self::PAGE_HIT, function ($tmp) use ($keyToId, &$counts) {
            $key = $tmp[0];

            if (isset($keyToId[$key])) {
                $id = $keyToId[$key];

                if (!isset($counts[$id])) {
                    $counts[$id] = 0;
                }

                $counts[$id]++;
            }
        });

        if (!$isOk) {
            return false;
        }

        $this->updateRatingsByEntity(Page::class, $counts);

        return true;
    }

    protected function walkFile(string $fileKey, callable $fn)
    {
        $file = $this->makeFile($fileKey);
        $fileCopy = $file . '_tmp';

        copy($file, $fileCopy);
        file_put_contents($file, '');

        $handle = fopen($fileCopy, 'r');

        while ($line = rtrim(fgets($handle))) {
            $tmp = explode(' ', $line);

            $fn($tmp);
        }

        fclose($handle);
        unlink($fileCopy);

        return true;
    }

    /**
     * @todo use this when "TRADITIONAL" sql_mode is disabled
     * @todo try update with self table join... (like fake item table order columns)
     *
     * @param           $entityClass
     * @param array $counts
     * @param bool|true $aggregate
     *
     * @return bool
     * @throws \Exception
     */
    protected function updateRatingsByEntity2($entityClass, array $counts, $aggregate = true)
    {
        $manager = $this->app->managers->getByEntityClass($entityClass);
        $entity = $manager->getEntity();

        if ($counts) {
            $tmp = [];

            foreach ($counts as $id => $count) {
                $tmp[] = '(' . $id . ', ' . $count . ')';
            }

            $db = $this->app->container->db;

            $db->req(implode(' ', [
                'INSERT' . ' INTO ' . $db->quote($entity->getTable()),
                '(' . $db->quote($entity->getPk()) . ', ' . $db->quote('rating') . ')',
                'VALUES',
                implode(', ', $tmp),
                'ON DUPLICATE KEY UPDATE ' . $db->quote('rating') . ' = VALUES(' . $db->quote('rating') . ')' . ($aggregate ? (' + ' . $db->quote('rating')) : '') . ',',
                $db->quote('updated_at') . ' = NOW()'
            ]));
        }

        return true;
    }

    protected function updateRatingsByEntity($entityClass, array $counts, $aggregate = true)
    {
        if ($counts) {
            $manager = $this->app->managers->getByEntityClass($entityClass);
            $entity = $manager->getEntity();

            $pk = $entity->getPk();
            $db = $this->app->container->db;

            $max = 1000;

            foreach ($counts as $id => $count) {
                if ($aggregate) {
                    $rating = new Expression('IF(' . $db->quote('rating') . ' + ? > ' . $max . ', ' . $max . ', ' . $db->quote('rating') . ' + ?)', $count, $count);
                } else {
                    $rating = max($count, $max);
                }

                $manager->updateMany(['rating' => $rating], [$pk => $id]);
            }
        }

        return true;
    }

    protected function makeFile(string $key): string
    {
        return str_replace('{key}', $key, $this->fileTemplate);
    }

    /**
     * @todo test new line after
     * @param string $fileKey
     * @param string $msg
     *
     * @return bool
     */
    protected function logHit(string $fileKey, string $msg)
    {
        if ($this->app->request->isCrawlerOrBot()) {
            return true;
        }

        file_put_contents($this->makeFile($fileKey), implode(' ', [
            $msg,
            $this->time,
            $this->app->request->getClientIp(),
            $this->app->request->getServer('HTTP_REFERER') ?: '-',
            $this->app->request->getServer('HTTP_USER_AGENT') ?: '-'
        ]), FILE_APPEND);

        return true;
    }

    public function updateRatings()
    {
        return $this->updatePagesRatingsByHits();
    }

    protected function dropPagesRatings()
    {
        $pk = Page::getPk();

        $counts = [];

        foreach (array_reverse($this->app->managers->pages->clear()
            ->setColumns($pk)
            ->setOrders(['rating' => SORT_DESC])
            ->getArrays()) as $i => $page) {
            $counts[$page[$pk]] = $i;
        }

        $this->app->managers->pages->updateMany(['rating' => 0]);

        $this->updateRatingsByEntity(Page::class, $counts, false);

        return true;
    }

    public function dropRatings()
    {
        return $this->dropPagesRatings();
    }
}