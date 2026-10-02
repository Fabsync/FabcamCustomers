<?php

class LicenseController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'product_id'  => $_GET['product_id']  ?? '',
            'amc_status'  => $_GET['amc_status']  ?? '',
            'customer_id' => (int)($_GET['customer_id'] ?? 0) ?: '',
            'q'           => trim($_GET['q'] ?? ''),
        ];
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $sort       = $_GET['sort'] ?? 'expiry_date';
        $dir        = $_GET['dir']  ?? 'asc';
        $perPage    = 20;
        $offset     = ($page - 1) * $perPage;

        $model      = new LicenseModel();
        $total      = $model->getCount($filters);
        $licenses   = $model->getAll($filters, $perPage, $offset, $sort, $dir);
        $products   = (new ProductModel())->getAll();
        $totalPages = (int) ceil($total / $perPage);

        $this->render('licenses/index', [
            'pageTitle'  => 'Licenses',
            'licenses'   => $licenses,
            'products'   => $products,
            'filters'    => $filters,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'perPage'    => $perPage,
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $customers = (new CustomerModel())->getAll();
        $products  = (new ProductModel())->getAll();

        // Pre-select customer from query string
        $preCustomer = (int)($_GET['customer_id'] ?? 0);

        $this->render('licenses/form', [
            'pageTitle'   => 'Add License',
            'license'     => ['customer_id' => $preCustomer],
            'customers'   => $customers,
            'products'    => $products,
            'errors'      => [],
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $d      = $this->sanitizePost();
        $errors = $this->validate($d);

        if ($errors) {
            $this->render('licenses/form', [
                'pageTitle' => 'Add License',
                'license'   => $d,
                'customers' => (new CustomerModel())->getAll(),
                'products'  => (new ProductModel())->getAll(),
                'errors'    => $errors,
            ]);
            return;
        }

        $model = new LicenseModel();
        $id    = $model->insert($d, $_SESSION['user_id']);
        $this->flash('success', 'License added successfully.');
        $this->redirect('/licenses/view/' . $id);
    }

    public function edit(string $id): void {
        $this->requireAuth();
        $model   = new LicenseModel();
        $license = $model->findById((int)$id);
        if (!$license) { $this->notFound(); return; }

        $this->render('licenses/form', [
            'pageTitle' => 'Edit License',
            'license'   => $license,
            'customers' => (new CustomerModel())->getAll(),
            'products'  => (new ProductModel())->getAll(),
            'errors'    => [],
        ]);
    }

    public function update(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $d      = $this->sanitizePost();
        $errors = $this->validate($d);

        $model   = new LicenseModel();
        $license = $model->findById((int)$id);
        if (!$license) { $this->notFound(); return; }

        if ($errors) {
            $this->render('licenses/form', [
                'pageTitle' => 'Edit License',
                'license'   => array_merge($license, $d),
                'customers' => (new CustomerModel())->getAll(),
                'products'  => (new ProductModel())->getAll(),
                'errors'    => $errors,
            ]);
            return;
        }

        $model->update((int)$id, $d, $_SESSION['user_id']);
        $this->flash('success', 'License updated successfully.');
        $this->redirect('/licenses/view/' . $id);
    }

    public function view(string $id): void {
        $this->requireAuth();
        $model   = new LicenseModel();
        $license = $model->findById((int)$id);
        if (!$license) { $this->notFound(); return; }

        $this->render('licenses/view', [
            'pageTitle' => 'License #' . $id,
            'license'   => $license,
        ]);
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        (new LicenseModel())->delete((int)$id);
        $this->flash('success', 'License deleted.');

        // Allow caller to redirect back to a specific internal path (e.g. customer view)
        $back = trim($_POST['_back'] ?? '');
        if ($back && str_starts_with($back, '/') && !str_contains($back, '//')) {
            $this->redirect($back);
        } else {
            $this->redirect('/licenses');
        }
    }

    private function sanitizePost(): array {
        return [
            'customer_id'    => (int)($_POST['customer_id']    ?? 0),
            'product_id'     => (int)($_POST['product_id']     ?? 0),
            'license_type'   => $_POST['license_type']   ?? 'single',
            'server_code'    => trim($_POST['server_code']    ?? ''),
            'lock_code'      => trim($_POST['lock_code']      ?? ''),
            'machine_name'   => trim($_POST['machine_name']   ?? ''),
            'purchase_price' => $_POST['purchase_price'] ?? '',
            'purchase_date'  => $_POST['purchase_date']  ?? '',
            'expiry_date'    => $_POST['expiry_date']    ?? '',
            'license_status' => $_POST['license_status'] ?? 'active',
            'amc_cost'       => $_POST['amc_cost']       ?? '',
            'renewal_date'   => $_POST['renewal_date']   ?? '',
            'amc_status'     => $_POST['amc_status']     ?? 'not_applicable',
            'remarks'        => trim($_POST['remarks']        ?? ''),
        ];
    }

    private function validate(array $d): array {
        $errors = [];
        if (empty($d['customer_id'])) $errors[] = 'Customer is required.';
        if (empty($d['product_id']))  $errors[] = 'Product is required.';
        if (empty($d['expiry_date'])) $errors[] = 'Expiry date is required.';
        $valid_types   = ['single','multi','server','cloud'];
        $valid_status  = ['active','expired','grace','revoked'];
        $valid_amc     = ['active','expired','not_applicable'];
        if (!in_array($d['license_type'],   $valid_types))  $errors[] = 'Invalid license type.';
        if (!in_array($d['license_status'], $valid_status)) $errors[] = 'Invalid license status.';
        if (!in_array($d['amc_status'],     $valid_amc))    $errors[] = 'Invalid AMC status.';
        return $errors;
    }

    private function notFound(): void {
        http_response_code(404);
        $this->render('errors/404', ['pageTitle' => 'Not Found']);
    }
}
