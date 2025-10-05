<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class YearlyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected Collection $data;
    protected string $reportType;
    protected int $year;

    public function __construct(Collection $data, string $reportType, int $year)
    {
        $this->data = $data;
        $this->reportType = $reportType;
        $this->year = $year;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->reportType === 'individual') {
            return [
                'Employee ID',
                'Employee Name',
                'Department',
                'Designation',
                'Present Days',
                'Absent Days',
                'Late Days',
                'Leave Days',
                'Total Late (min)',
                'Total Early (min)',
                'Attendance Rate (%)',
                'Punctuality Score',
                'Overall Performance',
            ];
        } elseif ($this->reportType === 'department') {
            return [
                'Department',
                'Total Employees',
                'Average Attendance Rate (%)',
                'Average Punctuality Score',
                'Total Absent Days',
                'Total Leave Days',
            ];
        } else {
            return [
                'Metric',
                'Value',
                'Description',
            ];
        }
    }

    public function map($row): array
    {
        if ($this->reportType === 'individual') {
            return [
                $row['user']['employee_id'],
                $row['user']['name'],
                $row['user']['department'] ?? 'N/A',
                $row['user']['designation'] ?? 'N/A',
                $row['attendance']['present'],
                $row['attendance']['absent'],
                $row['attendance']['late'],
                $row['leave']['total_leave_days'],
                $row['early_late']['total_late_minutes'],
                $row['early_late']['total_early_minutes'],
                $row['summary']['attendance_rate'],
                $row['summary']['punctuality_score'],
                $row['summary']['overall_performance'],
            ];
        } elseif ($this->reportType === 'department') {
            return [
                $row['department']['name'],
                $row['total_users'],
                $row['average_attendance_rate'],
                $row['average_punctuality_score'],
                $row['total_absent_days'],
                $row['total_leave_days'],
            ];
        } else {
            return [
                $row['metric'],
                $row['value'],
                $row['description'],
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
            
            // Style the header row
            'A1:M1' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        if ($this->reportType === 'individual') {
            return [
                'A' => 15, // Employee ID
                'B' => 25, // Employee Name
                'C' => 20, // Department
                'D' => 20, // Designation
                'E' => 15, // Present Days
                'F' => 15, // Absent Days
                'G' => 15, // Late Days
                'H' => 15, // Leave Days
                'I' => 18, // Total Late
                'J' => 18, // Total Early
                'K' => 18, // Attendance Rate
                'L' => 20, // Punctuality Score
                'M' => 20, // Overall Performance
            ];
        } elseif ($this->reportType === 'department') {
            return [
                'A' => 25, // Department
                'B' => 18, // Total Employees
                'C' => 25, // Average Attendance Rate
                'D' => 25, // Average Punctuality Score
                'E' => 20, // Total Absent Days
                'F' => 20, // Total Leave Days
            ];
        } else {
            return [
                'A' => 30, // Metric
                'B' => 20, // Value
                'C' => 50, // Description
            ];
        }
    }

    public function title(): string
    {
        $type = ucfirst($this->reportType);
        return "Yearly Report {$this->year} - {$type}";
    }
}