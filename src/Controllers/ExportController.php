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

        $colCount  = count($headers);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        foreach ($headers as $i => $label) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $label);
        }

        // Header row style — accent blue background, white bold text
        $headerRange = 'A1:' . $lastColLetter . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0067C0'],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // --- Data rows ---
        $rowNum = 2;
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

            foreach ($data as $i => $value) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col . $rowNum, $value);
            }

            // Alternate row shading
            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':' . $lastColLetter . $rowNum)
                      ->getFill()
                      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('F0F4F8');
            }

            $rowNum++;
        }

        // Auto-size columns
        foreach (range(1, $colCount) as $i) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Add auto-filter
        $sheet->setAutoFilter('A1:' . $lastColLetter . '1');

        // Stream download
        $filename = 'fabcam_licenses_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
