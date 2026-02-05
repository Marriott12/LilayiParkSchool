<?php
/**
 * Pagination Helper Class
 * Provides pagination functionality for list pages
 */

class Pagination {
    private $totalRecords;
    private $perPage;
    private $currentPage;
    private $totalPages;
    
    /**
     * Constructor
     */
    public function __construct($totalRecords, $perPage = 20, $currentPage = 1) {
        $this->totalRecords = (int)$totalRecords;
        $this->perPage = (int)$perPage;
        $this->currentPage = max(1, (int)$currentPage);
        $this->totalPages = ceil($this->totalRecords / $this->perPage);
        
        // Ensure current page doesn't exceed total pages
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }
    
    /**
     * Get SQL LIMIT clause
     */
    public function getLimitSQL() {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return "LIMIT {$this->perPage} OFFSET {$offset}";
    }
    
    /**
     * Get offset value
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    /**
     * Get limit value
     */
    public function getLimit() {
        return $this->perPage;
    }
    
    /**
     * Check if there are multiple pages
     */
    public function hasPages() {
        return $this->totalPages > 1;
    }
    
    /**
     * Get total pages
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * Get current page
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * Render pagination HTML
     */
    public function render($baseUrl = '') {
        if (!$this->hasPages()) {
            return '';
        }
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        if ($this->currentPage > 1) {
            $prevPage = $this->currentPage - 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($baseUrl, $prevPage) . '">Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }
        
        // Page numbers
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $this->currentPage + 2);
        
        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($baseUrl, 1) . '">1</a></li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($baseUrl, $i) . '">' . $i . '</a></li>';
            }
        }
        
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($baseUrl, $this->totalPages) . '">' . $this->totalPages . '</a></li>';
        }
        
        // Next button
        if ($this->currentPage < $this->totalPages) {
            $nextPage = $this->currentPage + 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($baseUrl, $nextPage) . '">Next</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }
        
        $html .= '</ul></nav>';
        
        // Records info
        $from = ($this->currentPage - 1) * $this->perPage + 1;
        $to = min($this->currentPage * $this->perPage, $this->totalRecords);
        $html .= '<p class="text-center text-muted small">Showing ' . $from . ' to ' . $to . ' of ' . $this->totalRecords . ' records</p>';
        
        return $html;
    }
    
    /**
     * Build URL with page parameter
     */
    private function buildUrl($baseUrl, $page) {
        if (empty($baseUrl)) {
            $baseUrl = $_SERVER['PHP_SELF'];
        }
        
        $params = $_GET;
        $params['page'] = $page;
        
        return $baseUrl . '?' . http_build_query($params);
    }
}
