<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LeaveSummaryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithMultipleSheets
{
    protected $data;
    protected $statistics;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $statistics, $startDate, $endDate)
    {
        $this->data = $data;
        $this->statistics = $statistics;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            'Leave Applications' => new LeaveSummaryApplicationsSheet($this->data, $this->startDate, $this->endDate),
            'Statistics' => new LeaveSummaryStatisticsSheet($this->statistics, $this->startDate, $this->endDate),
        ];
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee ID',
            'Employee Name',
            'Department',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days Count',
            'Status',
            'Reason',
            'Applied At',
            'Approved By',
            'Approved At',
            'Approval Notes',
        ];
    }

    public function map($application): array
    {
        return [
            $application['id'],
            $application['employee_id'],
            $application['employee_name'],
            $application['department'],
            $application['leave_type'],
            $application['start_date'],
            $application['end_date'],
            $application['days_count'],
            $application['status'],
            $application['reason'],
            $application['applied_at'],
            $application['approved_by'],
            $application['approved_at'],
            $application['approval_notes'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 15, // Employee ID
            'C' => 25, // Employee Name
            'D' => 20, // Department
            'E' => 20, // Leave Type
            'F' => 12, // Start Date
            'G' => 12, // End Date
            'H' => 12, // Days Count
            'I' => 12, // Status
            'J' => 30, // Reason
            'K' => 18, // Applied At
            'L' => 20, // Approved By
            'M' => 18, // Approved At
            'N' => 30, // Approval Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:N1')->applyFromArray([
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
        $sheet->getStyle("A2:N{$lastRow}")->applyFromArray([
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

        // Freeze first row
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return "Leave Summary Report";
    }
}

class LeaveSummaryApplicationsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee ID',
            'Employee Name',
            'Department',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days Count',
            'Status',
            'Reason',
            'Applied At',
            'Approved By',
            'Approved At',
            'Approval Notes',
        ];
    }

    public function map($application): array
    {
        return [
            $application['id'],
            $application['employee_id'],
            $application['employee_name'],
            $application['department'],
            $application['leave_type'],
            $application['start_date'],
            $application['end_date'],
            $application['days_count'],
            $application['status'],
            $application['reason'],
            $application['applied_at'],
            $application['approved_by'],
            $application['approved_at'],
            $application['approval_notes'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 15, // Employee ID
            'C' => 25, // Employee Name
            'D' => 20, // Department
            'E' => 20, // Leave Type
            'F' => 12, // Start Date
            'G' => 12, // End Date
            'H' => 12, // Days Count
            'I' => 12, // Status
            'J' => 30, // Reason
            'K' => 18, // Applied At
            'L' => 20, // Approved By
            'M' => 18, // Approved At
            'N' => 30, // Approval Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:N1')->applyFromArray([
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
        $sheet->getStyle("A2:N{$lastRow}")->applyFromArray([
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

        // Freeze first row
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return "Leave Applications";
    }
}

class LeaveSummaryStatisticsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $statistics;
    protected $startDate;
    protected $endDate;

    public function __construct($statistics, $startDate, $endDate)
    {
        $this->statistics = $statistics;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $data = collect();
        
        // Summary statistics
        $data->push([
            'type' => 'Summary',
            'category' => 'Total Applications',
            'value' => $this->statistics['total_applications'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Approved Applications',
            'value' => $this->statistics['approved_applications'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Pending Applications',
            'value' => $this->statistics['pending_applications'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Rejected Applications',
            'value' => $this->statistics['rejected_applications'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Total Leave Days',
            'value' => $this->statistics['total_leave_days'],
            'details' => ''
        ]);

        // By leave type
        foreach ($this->statistics['by_leave_type'] as $leaveType => $stats) {
            $data->push([
                'type' => 'By Leave Type',
                'category' => $leaveType,
                'value' => $stats['count'],
                'details' => "Days: {$stats['total_days']}, Approved: {$stats['approved_days']}"
            ]);
        }

        // By department
        foreach ($this->statistics['by_department'] as $department => $stats) {
            $data->push([
                'type' => 'By Department',
                'category' => $department,
                'value' => $stats['count'],
                'details' => "Days: {$stats['total_days']}, Approved: {$stats['approved_days']}"
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Type',
            'Category',
            'Value',
            'Details',
        ];
    }

    public function map($stat): array
    {
        return [
            $stat['type'],
            $stat['category'],
            $stat['value'],
            $stat['details'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Type
            'B' => 25, // Category
            'C' => 15, // Value
            'D' => 40, // Details
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:D1')->applyFromArray([
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
        $sheet->getStyle("A2:D{$lastRow}")->applyFromArray([
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

        return [];
    }

    public function title(): string
    {
        return "Statistics";
    }
}
