<?php

class EstimateController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'customer_id' => (int)($_GET['customer_id'] ?? 0) ?: '',
            'q'           => trim($_GET['q'] ?? ''),
        ];
        $model     = new EstimateModel();
        $estimates = $model->getAll($filters);
        $this->render('estimates/index', [
            'pageTitle' => 'Estimates',
            'estimates' => $estimates,
            'filters'   => $filters,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $this->render('estimates/form', [
            'pageTitle' => 'New Estimate',
            'estimate'  => [
                'estimate_date' => date('Y-m-d'),
                'valid_until'   => date('Y-m-d', strtotime('+30 days')),
                'tax_type'      => 'cgst_sgst',
                'tax_rate'      => 18,
                'discount_pct'  => 0,
                'status'        => 'draft',
                'notes'         => "Looking forward for your business.",
                'terms'         => "• Training: Onsite Training will be provided for a period of 1-2 days\n• Delivery: Two weeks from the date of providing purchase order, EULA copy & Advance Payment\n• Software Annual Maintenance contract will be 20% of Software Cost after 12th months\n• Scope of Support Maintenance Agreement (SMA)\n     - ESupport:\n     - Software Update:\n     - License Protection:\n     - Validity: SMA will be valid for 12 months\n• Permanent License: Key-Less Perpetual license will be issued after receipt of full payment\n• Payment Terms: 100% advance along with the Purchase Order & Signed EULA Copy.",
            ],
            'items'     => [],
            'customers' => (new CustomerModel())->getAll(),
            'errors'    => [],
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        [$d, $items, $errors] = $this->parsePost();
        if ($errors) {
            $this->render('estimates/form', [
                'pageTitle' => 'New Estimate',
                'estimate'  => $d,
                'items'     => $items,
                'customers' => (new CustomerModel())->getAll(),
                'errors'    => $errors,
            ]);
            return;
        }

        $id = (new EstimateModel())->insert($d, $items, $_SESSION['user_id']);
        $this->flash('success', 'Estimate created successfully.');
        $this->redirect('/estimates/view/' . $id);
    }

    public function edit(string $id): void {
        $this->requireAuth();
        $model    = new EstimateModel();
        $estimate = $model->findById((int)$id);
        if (!$estimate) { $this->notFound(); return; }

        $this->render('estimates/form', [
            'pageTitle' => 'Edit ' . $estimate['estimate_number'],
            'estimate'  => $estimate,
            'items'     => $model->findItems((int)$id),
            'customers' => (new CustomerModel())->getAll(),
            'errors'    => [],
        ]);
    }

    public function update(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $model    = new EstimateModel();
        $estimate = $model->findById((int)$id);
        if (!$estimate) { $this->notFound(); return; }

        [$d, $items, $errors] = $this->parsePost();
        if ($errors) {
            $this->render('estimates/form', [
                'pageTitle' => 'Edit ' . $estimate['estimate_number'],
                'estimate'  => array_merge($estimate, $d),
                'items'     => $items,
                'customers' => (new CustomerModel())->getAll(),
                'errors'    => $errors,
            ]);
            return;
        }

        $model->update((int)$id, $d, $items);
        $this->flash('success', 'Estimate updated.');
        $this->redirect('/estimates/view/' . $id);
    }

    public function view(string $id): void {
        $this->requireAuth();
        $model    = new EstimateModel();
        $estimate = $model->findById((int)$id);
        if (!$estimate) { $this->notFound(); return; }

        $this->render('estimates/view', [
            'pageTitle' => $estimate['estimate_number'],
            'estimate'  => $estimate,
            'items'     => $model->findItems((int)$id),
        ]);
    }

    public function generatePdf(string $id): void {
        $this->requireAuth();
        $model    = new EstimateModel();
        $estimate = $model->findById((int)$id);
        if (!$estimate) { $this->notFound(); return; }

        $items = $model->findItems((int)$id);

        ob_start();
        require __DIR__ . '/../Views/estimates/pdf_template.php';
        $html = ob_get_clean();

        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf_fab';
        if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0775, true); }

        $pubDir = realpath(dirname(__DIR__, 2) . '/public');

        $mpdf = new \Mpdf\Mpdf([
            'format'        => 'A4',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'tempDir'       => $tmpDir,
        ]);
        // Tell mPDF where to find local images (public/ directory)
        $mpdf->basepath = $pubDir ? str_replace('\\', '/', $pubDir) . '/' : '';
        $mpdf->SetTitle($estimate['estimate_number']);
        $mpdf->WriteHTML($html);
        $mpdf->Output($estimate['estimate_number'] . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->requireRole('admin');
        $this->validateCsrf();
        (new EstimateModel())->delete((int)$id);
        $this->flash('success', 'Estimate deleted.');
        $this->redirect('/estimates');
    }

    private function parsePost(): array {
        $d = [
            'customer_id'   => (int)($_POST['customer_id']  ?? 0),
            'estimate_date' => $_POST['estimate_date'] ?? date('Y-m-d'),
            'valid_until'   => trim($_POST['valid_until']   ?? ''),
            'discount_pct'  => max(0, min(100, (float)($_POST['discount_pct'] ?? 0))),
            'tax_type'      => $_POST['tax_type']      ?? 'cgst_sgst',
            'tax_rate'      => (float)($_POST['tax_rate']   ?? 18),
            'notes'         => trim($_POST['notes']         ?? ''),
            'terms'         => trim($_POST['terms']         ?? ''),
            'status'        => $_POST['status']        ?? 'draft',
        ];

        $valid_tax    = ['none', 'cgst_sgst', 'igst'];
        $valid_rates  = [0, 5, 12, 18, 28];
        $valid_status = ['draft', 'sent', 'accepted', 'cancelled'];
        if (!in_array($d['tax_type'], $valid_tax))      $d['tax_type'] = 'cgst_sgst';
        if (!in_array((int)$d['tax_rate'], $valid_rates)) $d['tax_rate'] = 18;
        if (!in_array($d['status'], $valid_status))     $d['status']   = 'draft';

        $items = [];
        $raw   = trim($_POST['_items'] ?? '[]');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!empty(trim($row['description'] ?? ''))) {
                    $items[] = [
                        'description' => trim($row['description']),
                        'hsn_sac'     => trim($row['hsn_sac']    ?? ''),
                        'quantity'    => max(0.001, (float)($row['quantity']   ?? 1)),
                        'unit'        => trim($row['unit']        ?? 'Nos') ?: 'Nos',
                        'unit_price'  => max(0, (float)($row['unit_price']  ?? 0)),
                    ];
                }
            }
        }

        $errors = [];
        if (empty($d['customer_id']))  $errors[] = 'Customer is required.';
        if (empty($d['estimate_date'])) $errors[] = 'Estimate date is required.';
        if (empty($items))             $errors[] = 'At least one line item with a description is required.';

        return [$d, $items, $errors];
    }

    private function notFound(): void {
        http_response_code(404);
        $this->render('errors/404', ['pageTitle' => 'Not Found']);
    }
}
