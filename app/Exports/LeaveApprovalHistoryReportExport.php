<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveApprovalHistoryReportExport implements WithMultipleSheets, WithTitle
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
            'Approval History' => new LeaveApprovalHistorySheet($this->data, $this->startDate, $this->endDate),
            'Statistics' => new LeaveApprovalStatisticsSheet($this->statistics, $this->startDate, $this->endDate),
        ];
    }

    public function title(): string
    {
        return "Leave Approval History Report";
    }
}

class LeaveApprovalHistorySheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
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
            'Processing Time (Hours)',
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
            $application['processing_time_hours'],
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
            'N' => 20, // Processing Time
            'O' => 30, // Approval Notes
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data styling
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:O{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Numeric columns alignment
        $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("H2:H{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("N2:N{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Freeze first row
        $sheet->freezePane('A2');

        return [];
    }

    public function title(): string
    {
        return "Approval History";
    }
}

class LeaveApprovalStatisticsSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
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
            'category' => 'Total Processed',
            'value' => $this->statistics['total_processed'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Approved Count',
            'value' => $this->statistics['approved_count'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Rejected Count',
            'value' => $this->statistics['rejected_count'],
            'details' => ''
        ]);
        
        $data->push([
            'type' => 'Summary',
            'category' => 'Average Processing Time (Hours)',
            'value' => round($this->statistics['average_processing_time_hours'], 2),
            'details' => ''
        ]);

        // By approver
        foreach ($this->statistics['by_approver'] as $approver => $stats) {
            $data->push([
                'type' => 'By Approver',
                'category' => $stats['approver'],
                'value' => $stats['total_processed'],
                'details' => "Approved: {$stats['approved']}, Rejected: {$stats['rejected']}, Rate: {$stats['approval_rate']}%, Avg Time: " . round($stats['average_processing_time'], 2) . "h"
            ]);
        }

        // By department
        foreach ($this->statistics['by_department'] as $department => $stats) {
            $data->push([
                'type' => 'By Department',
                'category' => $stats['department'],
                'value' => $stats['total_processed'],
                'details' => "Approved: {$stats['approved']}, Rejected: {$stats['rejected']}, Rate: {$stats['approval_rate']}%"
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
            'D' => 50, // Details
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data styling
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:D{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return "Statistics";
    }
}
