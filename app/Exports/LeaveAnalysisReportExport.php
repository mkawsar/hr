<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveAnalysisReportExport implements WithMultipleSheets, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new LeaveAnalysisSummarySheet($this->data),
            'Leave Type Analysis' => new LeaveAnalysisTypeSheet($this->data),
            'Department Analysis' => new LeaveAnalysisDepartmentSheet($this->data),
            'Monthly Trends' => new LeaveAnalysisMonthlySheet($this->data),
        ];
    }

    public function title(): string
    {
        return "Leave Analysis Report {$this->data['year']}";
    }
}

class LeaveAnalysisSummarySheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $summary = $this->data['summary'];
        
        return collect([
            [
                'metric' => 'Total Employees',
                'value' => $this->data['total_employees'],
                'description' => 'Number of employees with leave records'
            ],
            [
                'metric' => 'Total Leave Types',
                'value' => $this->data['total_leave_types'],
                'description' => 'Number of different leave types'
            ],
            [
                'metric' => 'Total Departments',
                'value' => $this->data['total_departments'],
                'description' => 'Number of departments'
            ],
            [
                'metric' => 'Total Allocated Days',
                'value' => $summary['total_allocated_days'],
                'description' => 'Total leave days allocated to all employees'
            ],
            [
                'metric' => 'Total Consumed Days',
                'value' => $summary['total_consumed_days'],
                'description' => 'Total leave days consumed by all employees'
            ],
            [
                'metric' => 'Total Remaining Days',
                'value' => $summary['total_remaining_days'],
                'description' => 'Total leave days remaining for all employees'
            ],
            [
                'metric' => 'Overall Utilization Rate',
                'value' => $summary['overall_utilization_rate'] . '%',
                'description' => 'Percentage of allocated leave days consumed'
            ],
            [
                'metric' => 'Total Applications',
                'value' => $summary['total_applications'],
                'description' => 'Total number of leave applications'
            ],
            [
                'metric' => 'Approval Rate',
                'value' => $summary['approval_rate'] . '%',
                'description' => 'Percentage of applications approved'
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Description',
        ];
    }

    public function map($item): array
    {
        return [
            $item['metric'],
            $item['value'],
            $item['description'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Metric
            'B' => 20, // Value
            'C' => 50, // Description
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:C1')->applyFromArray([
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
        $sheet->getStyle("A2:C{$lastRow}")->applyFromArray([
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
        return "Summary";
    }
}

class LeaveAnalysisTypeSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['leave_type_analysis']);
    }

    public function headings(): array
    {
        return [
            'Leave Type',
            'Total Allocated',
            'Total Consumed',
            'Total Balance',
            'Carry Forward',
            'Utilization Rate (%)',
            'Applications Count',
            'Approved',
            'Pending',
            'Rejected',
        ];
    }

    public function map($type): array
    {
        return [
            $type['leave_type'],
            $type['total_allocated'],
            $type['total_consumed'],
            $type['total_balance'],
            $type['total_carry_forward'],
            $type['utilization_rate'],
            $type['applications_count'],
            $type['approved_applications'],
            $type['pending_applications'],
            $type['rejected_applications'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Leave Type
            'B' => 15, // Total Allocated
            'C' => 15, // Total Consumed
            'D' => 15, // Total Balance
            'E' => 15, // Carry Forward
            'F' => 18, // Utilization Rate
            'G' => 18, // Applications Count
            'H' => 12, // Approved
            'I' => 12, // Pending
            'J' => 12, // Rejected
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:J1')->applyFromArray([
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
        $sheet->getStyle("A2:J{$lastRow}")->applyFromArray([
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
        $sheet->getStyle("B2:J{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return "Leave Type Analysis";
    }
}

class LeaveAnalysisDepartmentSheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['department_analysis']);
    }

    public function headings(): array
    {
        return [
            'Department',
            'Total Employees',
            'Total Allocated',
            'Total Consumed',
            'Total Balance',
            'Average Utilization (%)',
            'Applications Count',
            'Approved',
        ];
    }

    public function map($dept): array
    {
        return [
            $dept['department'],
            $dept['total_employees'],
            $dept['total_allocated'],
            $dept['total_consumed'],
            $dept['total_balance'],
            round($dept['average_utilization'], 2),
            $dept['applications_count'],
            $dept['approved_applications'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Department
            'B' => 15, // Total Employees
            'C' => 15, // Total Allocated
            'D' => 15, // Total Consumed
            'E' => 15, // Total Balance
            'F' => 20, // Average Utilization
            'G' => 18, // Applications Count
            'H' => 12, // Approved
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
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
        $sheet->getStyle("A2:H{$lastRow}")->applyFromArray([
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
        $sheet->getStyle("B2:H{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return "Department Analysis";
    }
}

class LeaveAnalysisMonthlySheet implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['monthly_trends']);
    }

    public function headings(): array
    {
        return [
            'Month',
            'Applications Count',
            'Approved Count',
            'Total Days',
        ];
    }

    public function map($month): array
    {
        return [
            $month['month'],
            $month['applications_count'],
            $month['approved_count'],
            $month['total_days'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Month
            'B' => 18, // Applications Count
            'C' => 15, // Approved Count
            'D' => 12, // Total Days
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

        // Numeric columns alignment
        $sheet->getStyle("B2:D{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return "Monthly Trends";
    }
}
