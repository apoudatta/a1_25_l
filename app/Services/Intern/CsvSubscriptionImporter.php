<?php
namespace App\Services\Intern;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CsvSubscriptionImporter
{
    public function __construct() {
    }


    public function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $highestRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $highestRow; $i++) { // Start from row 2 (skip header)
            //$colA = trim((string) $sheet->getCell("A$i")->getValue());
            $colB = trim((string) $sheet->getCell("B$i")->getValue());
            $colC = trim((string) $sheet->getCell("C$i")->getValue());
            $colD = trim((string) $sheet->getCell("D$i")->getValue());
            $colE = trim((string) $sheet->getCell("E$i")->getValue());
            $colF = trim((string) $sheet->getCell("F$i")->getValue());

            if ($colB === '' || $colC === '') continue;

            $rows[] = [$colB, $colC, $colD, $colE, $colF];
        }

        return $rows;
    }

    public function parseExcelGuest(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $highestRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $highestRow; $i++) { // Start from row 2 (skip header)
            //$colA = trim((string) $sheet->getCell("A$i")->getValue());
            $colB = trim((string) $sheet->getCell("B$i")->getValue());
            $colC = trim((string) $sheet->getCell("C$i")->getValue());
            $colD = trim((string) $sheet->getCell("D$i")->getValue());

            if ($colB === '' || $colC === '') continue;

            $rows[] = [$colB, $colC, $colD];
        }

        return $rows;
    }
}
