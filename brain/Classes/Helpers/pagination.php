<?php
/**
 * Class Pagination
 *
 * Utility class to handle paginated data.
 */
class Pagination
{
    protected int $totalItems;
    protected int $itemsPerPage;
    protected int $currentPage;
    protected int $totalPages;

    public function __construct(int $totalItems, int $itemsPerPage = 10, int $currentPage = 1)
    {
        $this->totalItems = max(0, $totalItems);
        $this->itemsPerPage = max(1, $itemsPerPage);
        $this->totalPages = (int) ceil($this->totalItems / $this->itemsPerPage);
        $this->currentPage = max(1, min($currentPage, $this->totalPages));
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}
