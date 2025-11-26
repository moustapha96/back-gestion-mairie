<?php

namespace App\services\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;
    /** @var string[]|null */
    private ?array $columns;

    /**
     * @param int $startRow   1-based start row
     * @param int $chunkSize  number of rows to read
     * @param string[]|null $columns list of column letters to allow (e.g. ['A','B',...])
     */
    public function __construct(int $startRow = 1, int $chunkSize = 100, ?array $columns = null)
    {
        $this->setRows($startRow, $chunkSize);
        $this->columns = $columns;
    }

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + max(1, $chunkSize) - 1;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row < $this->startRow || $row > $this->endRow) {
            return false;
        }
        if ($this->columns !== null && !in_array($columnAddress, $this->columns, true)) {
            return false;
        }
        // lis la cellule
        return true;
    }
}
