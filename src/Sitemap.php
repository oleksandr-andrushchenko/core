<?php

namespace SNOWGIRL_CORE;

/**
 * Creates sitemap.xml which includes inner files
 * Class Sitemap
 * @package SNOWGIRL_CORE
 * @see     https://www.sitemaps.org/protocol.html
 */
class Sitemap
{
    private $domain;
    private $dir;
    private $owner;
    private $perFile;
    private $currentItem;
    private $currentSitemap;

    private $searchEngines = [
        //yandex
        'http://webmaster.yandex.com/site/map.xml?host=',
        'http://ping.blogs.yandex.ru/ping?sitemap=',
//        'http://blogs.yandex.ru/pings/?status=success&url=',

        //google
        'http://google.com/ping?sitemap=',
//        'http://www.google.com/webmasters/sitemaps/ping?sitemap=',
//        'http;//www.google.com/webmasters/tools/ping?sitemap=',
//        'http://www.google.com/webmasters/tools/ping?sitemap=',
//        'http://www.google.com/ping?sitemap=',

        //yahoo
//        'http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?&url=',
//        'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=',

        //bing
//        'http://www.bing.com/webmaster/ping.aspx?siteMap=',
//        'http://www.bing.com/ping?sitemap=',

        //ask
//        'http://submissions.ask.com/ping?sitemap=',
    ];

    /**
     * @var callable|null
     */
    private $logger;

    private $filePrefix;
    private $fileTmpPrefix = 'tmp_';

    private $name;
    private $handle;
    private $gz;

    private $added;

    /**
     * @param string $domain - site name
     * @param string $dir - web-folder: where to create sitemap.xml and /sitemap folder
     * @param string|int $owner - web-server user
     * @param int $perFile - items per file
     * @param bool $gz - is need gz-compression
     * @param callable $logger
     */
    public function __construct(string $domain, string $dir, $owner, int $perFile = 50000, bool $gz = true, callable $logger = null)
    {
        $this->domain = rtrim($domain, '/');
        $this->dir = rtrim($dir, '/');
        $this->owner = $owner;
        $this->perFile = (int) $perFile;
        $this->gz = $gz;
        $this->logger = $logger;

        $this->filePrefix = time() . '_';
        $this->added = 0;

        $this->renew();
    }

    public function add($loc, $priority = 0.5, $changeFreq = null, $lastMod = null, $image = null, array $news = null)
    {
        if ($this->isNewFile()) {
            $this->endFile();
            $this->startFile();
        }

        $this->writeFile($loc, $priority, $changeFreq, $lastMod, $image, $news);
        $this->added++;

        return $this;
    }

    public function __destruct()
    {
        $this->endFile();
    }

    public function runWithName($name, callable $fn)
    {
        //close previous file if it was...
        $this->endFile();

        $this->name = $name;

        $this->renew();

        $fn($this);

        //close current file if it wasn't closed in callback
        $this->endFile();

        $this->name = null;

        return $this;
    }

    public function getHttpIndexFile()
    {
        return $this->domain . '/sitemap.xml';
    }

    public function create()
    {
        $this->endFile();

        $tmpIndex = $this->getTmpIndexFile();
        $index = $this->getIndexFile();

        $handle = fopen($tmpIndex, 'w');
        fputs($handle, '<?xml version="1.0" encoding="UTF-8"?>');
        fputs($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $files = glob($this->dir . '/sitemap/' . $this->filePrefix . '*.' . ($this->gz ? 'gz' : 'xml'));

        usort($files, function ($a, $b) {
            return filemtime($a) > filemtime($b);
        });

        foreach ($files as $file) {
            if (false === strpos($file, basename($this->fileTmpPrefix))) {
                $tmp = '<sitemap>';
                $tmp .= '<loc>' . $this->domain . '/sitemap/' . basename($file) . '</loc>';
                $tmp .= '<lastmod>' . date('c', filemtime($file)) . '</lastmod>';
                $tmp .= '</sitemap>';
                fputs($handle, $tmp);
            } else {
                $this->log('tmp "' . $file . '" file found');
            }
        }

        fputs($handle, '</sitemapindex>');
        fclose($handle);

        return $this->renameFile($tmpIndex, $index);
    }

    public function deleteOldFiles(): int
    {
        $aff = 0;

        foreach (glob($this->dir . '/sitemap/*.*') as $file) {
            if (false === strpos($file, basename($this->filePrefix))) {
                if ($this->deleteFile($file)) {
                    $aff++;
                } else {
                    $this->log('error on delete old file: ' . $file);
                }
            }
        }

        return $aff;
    }

    public function getAddedCount(): int
    {
        return $this->added;
    }

    public function submit()
    {
        $output = [];

        foreach ($this->searchEngines as $uri) {
            $url = $uri . htmlspecialchars($this->getHttpIndexFile(), ENT_QUOTES, 'UTF-8');
            $submitSite = curl_init($url);
            curl_setopt($submitSite, CURLOPT_RETURNTRANSFER, true);
            $responseContent = curl_exec($submitSite);
            $response = curl_getinfo($submitSite);
            $submitSiteShort = array_reverse(explode(".", parse_url($uri, PHP_URL_HOST)));

            $output[] = [
                "site" => $submitSiteShort[1] . "." . $submitSiteShort[0],
                "full_site" => $url,
                "http_code" => $response['http_code'],
                "message" => str_replace("\n", " ", strip_tags($responseContent))
            ];
        }

        return $output;
    }

    private function renew()
    {
        $this->currentSitemap = -1;
        $this->currentItem = 0;
        return $this;
    }

    private function getTmpInnerFile($gz = false)
    {
        return $this->dir . '/sitemap/' . $this->fileTmpPrefix . $this->filePrefix . ($this->name ?: 'default') . '_' . $this->currentSitemap . '.xml' . ($gz ? '.gz' : '');
    }

    private function getInnerFile($gz = false)
    {
        return $this->dir . '/sitemap/' . $this->filePrefix . ($this->name ?: 'default') . '_' . $this->currentSitemap . '.xml' . ($gz ? '.gz' : '');
    }

    private function isNewFile()
    {
        return 0 == $this->currentItem % $this->perFile;
    }

    private function startFile()
    {
        $this->currentSitemap++;

        if (!is_dir($this->dir . '/sitemap')) {
            mkdir($this->dir . '/sitemap', 0775, true);
            chown($this->dir . '/sitemap', $this->owner);
        }

        $this->handle = fopen($this->getTmpInnerFile(), 'w');
        fputs($this->handle, '<?xml version="1.0" encoding="UTF-8"?>');
        fputs($this->handle, '<urlset ' . implode(' ', [
                'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
                'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"',
                'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"'
            ]) . '>');

        return true;
    }

    private function writeFile($loc, $priority = 0.5, $changeFreq = null, $lastMod = null, $image = null, array $news = null)
    {
        $this->currentItem++;

        $tmp = '<url>';
        $tmp .= '<loc>' . $this->domain . htmlspecialchars($loc) . '</loc>';
        $tmp .= '<priority>' . $priority . '</priority>';

        if ($changeFreq) {
            $tmp .= '<changefreq>' . $changeFreq . '</changefreq>';
        }

        if ($lastMod) {
            $tmp .= '<lastmod>' . $lastMod . '</lastmod>';
        }

        if ($image) {
            //https://support.google.com/webmasters/answer/178636?hl=ru

            while (true) {
                if (!is_array($image)) {
                    $image = ['loc' => $image];
                }

                if (!isset($image['loc']) || !$image['loc']) {
                    break;
                }

                $tmp .= '<image:image>';
                $tmp .= '<image:loc>' . $image['loc'] . '</image:loc>';

                foreach (['caption', 'geo_location', 'title'] as $prop) {
                    if (isset($image[$prop])) {
                        $tmp .= '<image:' . $prop . '>' . htmlspecialchars($image[$prop]) . '</image:' . $prop . '>';
                    }
                }

                $tmp .= '</image:image>';

                break;
            }
        }

        if ($news) {
            //https://support.google.com/news/publisher/answer/74288?hl=ru

            while (true) {
                foreach (['publication', 'publication_date', 'title'] as $tag) {
                    if (!isset($news[$tag]) || !$news[$tag]) {
                        break;
                    }
                }

                if (!is_array($news['publication']) || !isset($news['publication']['name']) || !isset($news['publication']['language'])) {
                    break;
                }

                $tmp .= '<news:news>';
                $tmp .= '<news:publication>';

                foreach (['name', 'language'] as $prop) {
                    $tmp .= '<news:' . $prop . '>' . $news['publication'][$prop] . '</news:' . $prop . '>';
                }

                $tmp .= '</news:publication>';

                $tmp .= '<news:publication_date>' . $news['publication_date'] . '</news:publication_date>';
                $tmp .= '<news:title>' . htmlspecialchars($news['title']) . '</news:title>';

                foreach (['genres', 'keywords'] as $prop) {
                    if (isset($news[$prop])) {
                        $tmp .= '<news:' . $prop . '>' . htmlspecialchars($news[$prop]) . '</news:' . $prop . '>';
                    }
                }

                $tmp .= '</news:news>';

                break;
            }
        }

        $tmp .= '</url>';
        fputs($this->handle, $tmp);

        return true;
    }

    private function endFile()
    {
        if (!$this->handle) {
            return false;
        }

        fputs($this->handle, '</urlset>');
        fclose($this->handle);

        $this->handle = null;

        $tmpXmlName = $this->getTmpInnerFile();
        $xmlName = $this->getInnerFile();

        if ($this->renameFile($tmpXmlName, $xmlName)) {
            if ($this->gz) {
                $tmpGzName = $this->getTmpInnerFile(true);

                if ($this->gzFile($xmlName, $tmpGzName)) {
                    $this->deleteFile($xmlName);

                    $gzName = $this->getInnerFile(true);

                    $this->renameFile($tmpGzName, $gzName);
                }
            }
        }

        return true;
    }

    private function getTmpIndexFile()
    {
        return $this->dir . '/' . $this->fileTmpPrefix . 'sitemap.xml';
    }

    private function getIndexFile()
    {
        return $this->dir . '/sitemap.xml';
    }

    private function giveFilePermissions($target)
    {
        if (chmod($target, 0775) && chown($target, $this->owner)) {
            return true;
        }

        $this->log('can\'t give permissions to "' . $target . '"');
        return false;
    }

    private function renameFile($source, $target)
    {
        if (rename($source, $target)) {
            $this->giveFilePermissions($target);
            return true;
        }

        $this->log('can\'t rename "' . $source . '" file to "' . $target . '"');
        return false;
    }

    private function gzFile($source, $target, $level = 9)
    {
        $isOk = true;

        if ($zpOut = gzopen($target, 'wb' . $level)) {
            if ($zpIn = fopen($source, 'rb')) {
                while (!feof($zpIn)) {
                    gzwrite($zpOut, fread($zpIn, 1024 * 512));
                }

                fclose($zpIn);
            } else {
                $this->log('can\'t open "' . $source . '" file for binary reading');
                $isOk = false;
            }

            gzclose($zpOut);
        } else {
            $this->log('can\'t gz "' . $source . '" file to "' . $target . '"');
            $isOk = false;
        }

        if ($isOk) {
            $this->giveFilePermissions($target);
        }

        return $isOk;
    }

    private function deleteFile($target): bool
    {
        if (unlink($target)) {
            return true;
        }

        $this->log('can\'t delete "' . $target . '" file');
        return false;
    }

    private function log($msg)
    {
        is_callable($this->logger) && call_user_func($this->logger, $msg);
        return $this;
    }
}
