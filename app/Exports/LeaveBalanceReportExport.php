<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LeaveBalanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $year;

    public function __construct($data, $year)
    {
        $this->data = $data;
        $this->year = $year;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Designation',
            'Leave Type',
            'Leave Code',
            'Balance',
            'Consumed',
            'Accrued',
            'Carry Forward',
            'Total Available',
        ];
    }

    public function map($employee): array
    {
        $rows = [];
        
        foreach ($employee['leave_balances'] as $balance) {
            $rows[] = [
                $employee['employee_id'],
                $employee['name'],
                $employee['department'],
                $employee['designation'],
                $balance['leave_type'],
                $balance['leave_code'],
                $balance['balance'],
                $balance['consumed'],
                $balance['accrued'],
                $balance['carry_forward'],
                $balance['total_available'],
            ];
        }
        
        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee ID
            'B' => 25, // Employee Name
            'C' => 20, // Department
            'D' => 20, // Designation
            'E' => 20, // Leave Type
            'F' => 15, // Leave Code
            'G' => 12, // Balance
            'H' => 12, // Consumed
            'I' => 12, // Accrued
            'J' => 15, // Carry Forward
            'K' => 15, // Total Available
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data styling
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:K{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Numeric columns alignment
        $sheet->getStyle("G2:K{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Freeze first row
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return "Leave Balance Report {$this->year}";
    }
}
