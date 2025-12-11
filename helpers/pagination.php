<?php

class Pagination
{
    private $currentPage;
    private $totalItems;
    private $itemsPerPage;
    private $totalPages;
    private $baseUrl;
    private $queryParams;

    public function __construct($totalItems, $itemsPerPage = ITEMS_PER_PAGE, $currentPage = 1, $baseUrl = '', $queryParams = [])
    {
        $this->totalItems = (int)$totalItems;
        $this->itemsPerPage = (int)$itemsPerPage;
        $this->currentPage = max(1, (int)$currentPage);
        $this->baseUrl = $baseUrl ?: $_SERVER['REQUEST_URI'];
        $this->queryParams = $queryParams;

        $this->totalPages = ceil($this->totalItems / $this->itemsPerPage);

        // Ensure current page doesn't exceed total pages
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }

    /**
     * Get current page number
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get total number of pages
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * Get total number of items
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * Get items per page
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * Get offset for database query
     */
    public function getOffset()
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Get limit for database query
     */
    public function getLimit()
    {
        return $this->itemsPerPage;
    }

    /**
     * Check if there are more pages
     */
    public function hasNextPage()
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Check if there are previous pages
     */
    public function hasPrevPage()
    {
        return $this->currentPage > 1;
    }

    /**
     * Get next page number
     */
    public function getNextPage()
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    /**
     * Get previous page number
     */
    public function getPrevPage()
    {
        return $this->hasPrevPage() ? $this->currentPage - 1 : null;
    }

    /**
     * Check if current page is valid
     */
    public function isValidPage()
    {
        return $this->currentPage >= 1 && $this->currentPage <= $this->totalPages;
    }

    /**
     * Get page range for display
     */
    public function getPageRange($maxPages = MAX_PAGES_DISPLAY)
    {
        $half = floor($maxPages / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->totalPages, $start + $maxPages - 1);

        // Adjust start if we're near the end
        $start = max(1, $end - $maxPages + 1);

        return range($start, $end);
    }

    /**
     * Generate pagination links (Bootstrap compatible)
     */
    public function render()
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination justify-content-center">';

        // Previous button
        if ($this->hasPrevPage()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getPrevPage()) . '" aria-label="Previous">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Previous">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</span>';
            $html .= '</li>';
        }

        // Page numbers
        $pageRange = $this->getPageRange();

        // First page and ellipsis
        if ($pageRange[0] > 1) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl(1) . '">1</a>';
            $html .= '</li>';

            if ($pageRange[0] > 2) {
                $html .= '<li class="page-item disabled">';
                $html .= '<span class="page-link">...</span>';
                $html .= '</li>';
            }
        }

        // Page range
        foreach ($pageRange as $page) {
            $activeClass = ($page == $this->currentPage) ? ' active' : '';
            $html .= '<li class="page-item' . $activeClass . '">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($page) . '">' . $page . '</a>';
            $html .= '</li>';
        }

        // Last page and ellipsis
        if (end($pageRange) < $this->totalPages) {
            if (end($pageRange) < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled">';
                $html .= '<span class="page-link">...</span>';
                $html .= '</li>';
            }

            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->totalPages) . '">' . $this->totalPages . '</a>';
            $html .= '</li>';
        }

        // Next button
        if ($this->hasNextPage()) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->getNextPage()) . '" aria-label="Next">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Next">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</span>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Generate page URL
     */
    private function getPageUrl($page)
    {
        $params = array_merge($this->queryParams, ['page' => $page]);

        // Remove page parameter if it's 1 (for cleaner URLs)
        if ($page == 1 && !isset($this->queryParams['page'])) {
            unset($params['page']);
        }

        return $this->buildUrl($this->baseUrl, $params);
    }

    /**
     * Build URL with parameters
     */
    private function buildUrl($baseUrl, $params = [])
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $queryString = http_build_query($params);
        $separator = strpos($baseUrl, '?') === false ? '?' : '&';

        return $baseUrl . $separator . $queryString;
    }

    /**
     * Get pagination info
     */
    public function getInfo()
    {
        $startItem = ($this->currentPage - 1) * $this->itemsPerPage + 1;
        $endItem = min($this->currentPage * $this->itemsPerPage, $this->totalItems);

        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'total_items' => $this->totalItems,
            'items_per_page' => $this->itemsPerPage,
            'start_item' => $startItem,
            'end_item' => $endItem,
            'has_next' => $this->hasNextPage(),
            'has_prev' => $this->hasPrevPage(),
            'next_page' => $this->getNextPage(),
            'prev_page' => $this->getPrevPage()
        ];
    }

    /**
     * Get pagination summary text
     */
    public function getSummary($itemName = 'items')
    {
        $info = $this->getInfo();

        if ($info['total_items'] == 0) {
            return "No $itemName found";
        }

        return "Showing {$info['start_item']} to {$info['end_item']} of {$info['total_items']} $itemName";
    }

    /**
     * Create pagination from array
     */
    public static function fromArray($array, $itemsPerPage = ITEMS_PER_PAGE, $currentPage = 1, $baseUrl = '', $queryParams = [])
    {
        $totalItems = is_array($array) ? count($array) : 0;
        return new self($totalItems, $itemsPerPage, $currentPage, $baseUrl, $queryParams);
    }

    /**
     * Create simple pagination links array
     */
    public function getLinksArray()
    {
        $links = [];

        if ($this->totalPages <= 1) {
            return $links;
        }

        // Previous
        if ($this->hasPrevPage()) {
            $links[] = [
                'url' => $this->getPageUrl($this->getPrevPage()),
                'label' => 'Previous',
                'active' => false,
                'disabled' => false
            ];
        }

        // Page numbers
        $pageRange = $this->getPageRange();

        // First page
        if ($pageRange[0] > 1) {
            $links[] = [
                'url' => $this->getPageUrl(1),
                'label' => '1',
                'active' => false,
                'disabled' => false
            ];

            if ($pageRange[0] > 2) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true
                ];
            }
        }

        // Page range
        foreach ($pageRange as $page) {
            $links[] = [
                'url' => $this->getPageUrl($page),
                'label' => (string)$page,
                'active' => ($page == $this->currentPage),
                'disabled' => false
            ];
        }

        // Last page
        if (end($pageRange) < $this->totalPages) {
            if (end($pageRange) < $this->totalPages - 1) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true
                ];
            }

            $links[] = [
                'url' => $this->getPageUrl($this->totalPages),
                'label' => (string)$this->totalPages,
                'active' => false,
                'disabled' => false
            ];
        }

        // Next
        if ($this->hasNextPage()) {
            $links[] = [
                'url' => $this->getPageUrl($this->getNextPage()),
                'label' => 'Next',
                'active' => false,
                'disabled' => false
            ];
        }

        return $links;
    }
}
