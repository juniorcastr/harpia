<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RegistrosPontoExport implements FromArray, WithColumnWidths
{
    public function __construct(
        private array $data
    ) {
    }

    public function array(): array
    {
        return $this->data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 15,
            'C' => 30,
            'D' => 14,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 18,
            'I' => 18,
            'J' => 12,
        ];
    }
}
