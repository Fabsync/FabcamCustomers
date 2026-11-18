<?php

class ExportController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $this->requireRole('admin');

        $products  = (new ProductModel())->getAll();
        $customers = (new CustomerModel())->getAll();

        $this->render('export/index', [
            'pageTitle' => 'Export',
            'products'  => $products,
            'customers' => $customers,
        ]);
    }

    public function download(): void {
        $this->requireAuth();
        $this->requireRole('admin');

        $filters = [
            'status'      => $_GET['status']      ?? '',
            'amc_status'  => $_GET['amc_status']  ?? '',
            'product_id'  => $_GET['product_id']  ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
        ];

        (new LicenseModel())->expireOverdueAmc();
        $rows = (new ExportModel())->getLicenseExportData($filters);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Licenses');

        // --- Headers ---
        $headers = [
            'Customer ID', 'Company Name', 'Contact Person', 'Mobile', 'Email',
            'GST Number', 'Address',
            'Product', 'License Type', 'Machine Name', 'Server Code', 'Lock Code',
            'Purchase Date', 'Expiry Date', 'Days Left', 'License Status',
            'Purchase Price (INR)', 'AMC Cost (INR)', 'Renewal Date', 'AMC Status',
            'Remarks',
        ];

        $data_rows = [];
        foreach ($rows as $row) {
            $data = [
                $row['cust_code']     ?? '',
                $row['company_name']  ?? '',
                $row['contact_person'] ?? '',
                $row['mobile']        ?? '',
                $row['cust_email']    ?? '',
                $row['gst_number']    ?? '',
                $row['address']       ?? '',
                $row['product_name']  ?? '',
                ucfirst($row['license_type'] ?? ''),
                $row['machine_name']  ?? '',
                $row['server_code']   ?? '',
                $row['lock_code']     ?? '',
                $row['purchase_date'] ?? '',
                $row['expiry_date']   ?? '',
                (int) ($row['days_left'] ?? 0),
                ucfirst($row['license_status'] ?? ''),
                $row['purchase_price'] !== null && $row['purchase_price'] !== '' ? (float) $row['purchase_price'] : '',
                $row['amc_cost']      !== null && $row['amc_cost']      !== '' ? (float) $row['amc_cost'] : '',
                $row['renewal_date']  ?? '',
                ucfirst(str_replace('_', ' ', $row['amc_status'] ?? '')),
                $row['remarks']       ?? '',
            ];

            $data_rows[] = $data;
        }
        $this->fillSheet($sheet, $headers, $data_rows);

        // --- Second tab: Leads ---
        $leadSheet = $spreadsheet->createSheet();
        $leadSheet->setTitle('Leads');
        $leadHeaders = [
            'Lead No', 'Company Name', 'Contact Person', 'Mobile', 'Email',
            'Product', 'Requirement', 'Source', 'Status', 'Interest',
            'Expected Value (INR)', 'Next Follow-up', 'Close Reason', 'Closed At',
            'Customer ID', 'Assigned To', 'Created By', 'Created At', 'Updated At',
        ];
        $leadRows = [];
        foreach ((new ExportModel())->getLeadExportData() as $l) {
            $leadRows[] = [
                $l['lead_number'] ?? '',
                $l['company_name'] ?? '',
                $l['contact_person'] ?? '',
                $l['mobile'] ?? '',
                $l['email'] ?? '',
                $l['product_name'] ?? '',
                $l['requirement'] ?? '',
                LeadModel::SOURCES[$l['source'] ?? ''] ?? ($l['source'] ?? ''),
                LeadModel::STATUSES[$l['status'] ?? ''] ?? ($l['status'] ?? ''),
                LeadModel::INTEREST_LEVELS[$l['interest_level'] ?? ''] ?? ($l['interest_level'] ?? ''),
                $l['expected_value'] !== null && $l['expected_value'] !== '' ? (float) $l['expected_value'] : '',
                $l['next_follow_up'] ?? '',
                $l['close_reason'] ?? '',
                $l['closed_at'] ?? '',
                $l['cust_code'] ?? '',
                $l['assigned_to_name'] ?? '',
                $l['created_by_name'] ?? '',
                $l['created_at'] ?? '',
                $l['updated_at'] ?? '',
            ];
        }
        $this->fillSheet($leadSheet, $leadHeaders, $leadRows);
        $spreadsheet->setActiveSheetIndex(0);

        // Stream download
        $filename = 'fabcam_licenses_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** Writes a styled header row + data rows, auto-size, freeze pane and filter. */
    private function fillSheet($sheet, array $headers, array $rows): void {
        $coord = '\PhpOffice\PhpSpreadsheet\Cell\Coordinate';
        $colCount = count($headers);
        $last = $coord::stringFromColumnIndex($colCount);

        foreach ($headers as $i => $label) {
            $sheet->setCellValue($coord::stringFromColumnIndex($i + 1) . '1', $label);
        }
        $sheet->getStyle('A1:' . $last . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0067C0'],
            ],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $rowNum = 2;
        foreach ($rows as $data) {
            foreach ($data as $i => $value) {
                $sheet->setCellValue($coord::stringFromColumnIndex($i + 1) . $rowNum, $value);
            }
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':' . $last . $rowNum)
                      ->getFill()
                      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('F0F4F8');
            }
            $rowNum++;
        }

        foreach (range(1, $colCount) as $i) {
            $sheet->getColumnDimension($coord::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $last . '1');
    }
}
