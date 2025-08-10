<?php
/**
 * Class SGrid
 *
 * A simple data grid utility for tabular data display and manipulation.
 */
class SGrid
{
    protected array $columns = [];
    protected array $rows = [];

    /**
     * Define the columns for the grid.
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add a single row to the grid.
     */
    public function addRow(array $row): self
    {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Set multiple rows at once.
     */
    public function setRows(array $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Render the grid as an HTML table.
     */
    public function render(): string
    {
        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        if (!empty($this->columns)) {
            $html .= '<thead><tr>';
            foreach ($this->columns as $column) {
                $html .= '<th>' . htmlspecialchars($column) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach ($this->columns as $key) {
                $value = $row[$key] ?? '';
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }
}
