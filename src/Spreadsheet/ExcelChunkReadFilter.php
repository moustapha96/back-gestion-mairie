<?php
declare(strict_types=1);

namespace App\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Filtre de lecture "streaming" pour PhpSpreadsheet.
 * Permet de ne charger qu'une fenêtre (chunk) de lignes et un set de colonnes.
 */
final class ExcelChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;
    /** @var string[] */
    private array $columns;

    /**
     * @param int $startRow 1-indexed
     * @param int $chunkSize nombre de lignes à lire à partir de startRow
     * @param array<string> $columns ex: range('A','T')
     */
    public function __construct(int $startRow, int $chunkSize, array $columns)
    {
        $this->setRows($startRow, $chunkSize);
        $this->columns = $columns;
    }

    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize - 1;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row < $this->startRow || $row > $this->endRow) {
            return false;
        }
        // Restriction aux colonnes utiles (perf + mémoire)
        return \in_array($columnAddress, $this->columns, true);
    }
}
