<?php

namespace RightNow\Helpers;

use RightNow\Utils\Url;

/**
 * Commons functions for use with the different pagination widgets
 */
class PaginationHelper {
    /**
     * Constructs a pagination href value.
     * @param  int $pageNumber Page number
     * @param  array  $pageFilter Paging filter
     * @return string CP URL
     */
    function pageLink ($pageNumber, array $pageFilter) {
        static $page;
        $page || ($page = \RightNow\Utils\Text::removeTrailingSlash($_SERVER['REQUEST_URI']));

        return Url::addParameter($page, $pageFilter['key'], $pageNumber) . Url::sessionParameter();
    }

    /**
     * Checks if the given page is the current page or not.
     * @param integer $pageNumber Arbitrary page number
     * @param integer $currentPage Current/clicked page number
     * @return bool True if the page numbers match
     */
    function isCurrentPage($pageNumber, $currentPage) {
        return $pageNumber === $currentPage;
    }

    /**
     * Inserts the given page numbers into the given format string.
     * @param string $labelPage Label to display
     * @param integer $pageNumber Page number
     * @param integer $endPage Last page number in the pagination
     * @return string Sprintf-d string
     */
    function paginationLinkTitle($labelPage, $pageNumber, $endPage) {
        return sprintf($labelPage, $pageNumber, $endPage);
    }

    /**
     * Determines if a hellip should be displayed.
     * @param integer $pageNumber Page number to check
     * @param integer $currentPage Current/clicked page number
     * @param integer $endPage Last page number in the pagination
     * @return bool True if the hellip should be displayed
     */
    function shouldShowHellip($pageNumber, $currentPage, $endPage) {
        return abs($pageNumber - $currentPage) === (($currentPage === 1 || $currentPage === $endPage) ? 3 : 2);
    }

    /**
     * Determines if the given page number should be displayed.
     * The pagination pattern followed here is:
     *     1 ... 4 5 6 ... 12.
     * if, for example, 5 is the current/clicked page out of a total of 12 pages.
     * @param integer $pageNumber Page number to check
     * @param integer $currentPage Current/clicked page number
     * @param integer $endPage Last page number in the pagination
     * @return bool True if the page number should be displayed.
     */
    function shouldShowPageNumber($pageNumber, $currentPage, $endPage) {
        // Always display the first and last pages.
        // Display the next (or previous) two pages when you're on the first or last page.
        // Unless you're on other pages, in which case we want to display page numbers adjacent to the current page only.
        return $pageNumber === 1 || ($pageNumber === $endPage) || (abs($pageNumber - $currentPage) <= (($currentPage === 1 || $currentPage === $endPage) ? 2 : 1));
    }
}
