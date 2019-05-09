<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 20.10.14
 * Time: 23:16
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\Helper;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget;

/**
 * Class Pager
 * @package SNOWGIRL_CORE\View\Widget
 */
class Pager extends Widget
{
    protected $staticLink;
    protected $total;
    protected $pageSize;
    protected $pageNumber;
    protected $pagesPerSet;
    protected $pageParam;
    protected $pageSetPages;
    protected $lastPage;
    protected $totalItems;
    protected $totalPages;
    protected $firstPage = 1;
    protected $first;
    protected $last;
    protected $firstPageInSet;
    protected $lastPageInSet;
    protected $entriesOnCurrentPage;
    protected $previousPage;
    protected $nextPage;
    protected $pageSetPrevious;
    protected $pageSetNext;
    protected $linkAttr;
    protected $showStatistic;

    protected function initialize()
    {
        $this->calculate();
        return parent::initialize();
    }

    protected function makeParams(array $params = [])
    {
        return array_merge($params, [
            'staticLink' => $params['link'] ?? $_SERVER['REQUEST_URI'],
            'pageParam' => $params['param'] ?? 'page',
            'total' => (int)($params['total'] ?? 0),
            'pageSize' => (int)($params['size'] ?? 10),
            'pageNumber' => (int)($params['page'] ?? 1),
            'pagesPerSet' => (int)($params['per_set'] ?? 5),
            'linkAttr' => $params['attrs'] ?? '',
            'showStatistic' => (bool)($params['statistic'] ?? true),
            'pageSetPages' => []
        ]);
    }

    protected function calculate()
    {
        # Calculate the total pages & the last page number
        $this->lastPage = intval($this->total / $this->pageSize);

        if ($this->total % $this->pageSize) {
            $this->lastPage++;
        }

        if ($this->lastPage < 1) {
            $this->lastPage = 1;
        }

        $this->totalPages = $this->lastPage;

        if ($this->pageNumber > $this->lastPage) {
            $this->pageNumber = $this->lastPage;
        }

        $this->firstPage = 1; #always = 1

        # calculate the first data entry on the current page
        if ($this->total == 0) {
            $this->first = 0;
        } else {
            $this->first = (($this->pageNumber - 1) * $this->pageSize) + 1;
        }

        # calculate the last data entry on the current page
        if ($this->pageNumber == $this->lastPage) {
            $this->last = $this->total;
        } else {
            $this->last = ($this->pageNumber * $this->pageSize);
        }

        # Calculate entries on the current page
        if ($this->total == 0) {
            $this->entriesOnCurrentPage = 0;
        } else {
            $this->entriesOnCurrentPage = $this->last - $this->first + 1;
        }

        #calculate the previous page number if any
        if ($this->pageNumber > 1) {
            $this->previousPage = $this->pageNumber - 1;
        } else {
            $this->previousPage = null;
        }

        #calculate the next page number if any
        $this->nextPage = $this->pageNumber < $this->lastPage ? $this->pageNumber + 1 : null;

        #calculate pages sets
        $this->calculateVisiblePages();

        #check if the first page is currently in the pages set displayed
        $this->firstPageInSet = $this->pageSetPages[0] == 1 ? 1 : 0;

        #check if the last page is currently in the pages set displayed
        $this->lastPageInSet = end($this->pageSetPages) == $this->lastPage ? 1 : 0;
    }

    public function getTotalPageCount()
    {
        return $this->lastPage;
    }

    public function getCurrentPageNumber()
    {
        return $this->pageNumber;
    }

    public function getEntriesOnCurrentPage()
    {
        if ($this->total == 0) {
            return 0;
        }

        return $this->last - $this->first + 1;
    }

    public function calculateVisiblePages()
    {
        //unless ( $this->pagesPerSet > 1 ) {
        if ($this->pagesPerSet <= 1) {
            # Only have one page in the set, must be page 1
            if ($this->pageNumber != 1) {
                $this->pageSetPrevious = $this->pageNumber - 1;
            }

            $this->pageSetPages = ['1'];

            if ($this->pageNumber < $this->lastPage) {
                $this->pageSetNext = $this->pageNumber + 1;
            }

        } else {
            # See if we have enough pages to slide
            if ($this->pagesPerSet >= $this->lastPage) {

                # No sliding, no next/prev pageset
                $this->pageSetPages = range(1, $this->lastPage);
            } else {

                # Find the middle rounding down - we want more pages after, than before
                $middle = intval($this->pagesPerSet / 2);

                # offset for extra value right of center on even numbered sets
                $offset = 1;

                if ($this->pagesPerSet % 2 != 0) {
                    # must have been an odd number, add one
                    $middle++;
                    $offset = 0;
                }

                $startingPage = $this->pageNumber - $middle + 1;

                if ($startingPage < 1) {
                    $startingPage = 1;
                }

                $endPage = $startingPage + $this->pagesPerSet - 1;

                if ($this->lastPage < $endPage) {
                    $endPage = $this->lastPage;
                }

                if ($this->pageNumber <= $middle) {
                    # near the start of the page numbers
                    $this->pageSetNext = $this->pagesPerSet + $middle - $offset;
                    $this->pageSetPages = range(1, $this->pagesPerSet);
                } elseif ($this->pageNumber > ($this->lastPage - $middle - $offset)) {
                    # near the end of the page numbers
                    $this->pageSetPrevious = $this->lastPage - $this->pagesPerSet - $middle + 1;
                    $this->pageSetPages = range($this->lastPage - $this->pagesPerSet + 1, $this->lastPage);
                } else {
                    # Start scrolling
                    $this->pageSetPages = range($startingPage, $endPage);
                    $this->pageSetPrevious = $startingPage - $middle - $offset;

                    if ($this->pageSetPrevious < 1) {
                        $this->pageSetPrevious = 1;
                    }

                    $this->pageSetNext = $endPage + $middle;
                }
            }
        }
    }

    public function getFirstPageInCurrentSet()
    {
        $currentPageSet = 0;

        if ($this->pagesPerSet > 0) {
            $currentPageSet = intval($this->pageNumber / $this->pagesPerSet);

            if ($this->pageNumber % $this->pagesPerSet == 0) {
                $currentPageSet = $currentPageSet - 1;
            }
        }

        return ($currentPageSet * $this->pagesPerSet) + 1;
    }

    public function isOk()
    {
        return $this->total > $this->pageSize;
    }

    public function isLastPage()
    {
        return $this->pageNumber == $this->lastPage;
    }

    protected function stringifyWidget($template)
    {
        if ($this->showStatistic) {
            $this->totalItems = Helper::makeNiceNumber($this->total);
            $this->totalPages = Helper::makeNiceNumber($this->lastPage);
        }

        return parent::stringifyWidget($template);
    }
}