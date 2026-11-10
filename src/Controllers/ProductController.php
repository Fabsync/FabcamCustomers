<?php

class ProductController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $products = (new ProductModel())->getAll();
        $this->render('products/index', [
            'pageTitle' => 'Products',
            'products'  => $products,
            'canManage' => self::canManage(),
        ]);
    }

    public function create(): void {
        $this->requireProductManager();
        $this->render('products/form', [
            'pageTitle' => 'Add Product',
            'product'   => [],
            'errors'    => [],
        ]);
    }

    public function store(): void {
        $this->requireProductManager();
        $this->validateCsrf();
        $d      = $this->sanitizePost();
        $errors = $this->validate($d);

        if ($errors) {
            $this->render('products/form', ['pageTitle'=>'Add Product','product'=>$d,'errors'=>$errors]);
            return;
        }
        $id = (new ProductModel())->insert($d);
        $this->flash('success', 'Product added.');
        $this->redirect('/products');
    }

    public function edit(string $id): void {
        $this->requireProductManager();
        $product = (new ProductModel())->findById((int)$id);
        if (!$product) { http_response_code(404); die('Not found'); }
        $this->render('products/form', ['pageTitle'=>'Edit Product','product'=>$product,'errors'=>[]]);
    }

    public function update(string $id): void {
        $this->requireProductManager();
        $this->validateCsrf();
        $d      = $this->sanitizePost();
        $errors = $this->validate($d);
        $model  = new ProductModel();
        $product = $model->findById((int)$id);
        if (!$product) { http_response_code(404); die('Not found'); }

        if ($errors) {
            $this->render('products/form', ['pageTitle'=>'Edit Product','product'=>array_merge($product,$d),'errors'=>$errors]);
            return;
        }
        $model->update((int)$id, $d);
        $this->flash('success', 'Product updated.');
        $this->redirect('/products');
    }

    public function delete(string $id): void {
        $this->requireProductManager();
        $this->validateCsrf();
        try {
            (new ProductModel())->delete((int)$id);
            $this->flash('success', 'Product deleted.');
        } catch (PDOException $e) {
            $this->flash('danger', 'Cannot delete: product is assigned to licenses.');
        }
        $this->redirect('/products');
    }

    // Roles allowed to add / edit / delete products
    const MANAGER_ROLES = ['admin', 'sales'];

    public static function canManage(): bool {
        return in_array($_SESSION['user_role'] ?? '', self::MANAGER_ROLES, true);
    }

    private function requireProductManager(): void {
        $this->requireAuth();
        if (!self::canManage()) {
            http_response_code(403);
            $viewPath   = __DIR__ . '/../Views/errors/403.php';
            $layoutPath = __DIR__ . '/../Views/layouts/app.php';
            $pageTitle  = 'Access Denied';
            require $layoutPath;
            exit;
        }
    }

    private function sanitizePost(): array {
        return [
            'product_name' => trim($_POST['product_name'] ?? ''),
            'module'       => trim($_POST['module']       ?? ''),
            'description'  => trim($_POST['description']  ?? ''),
        ];
    }

    private function validate(array $d): array {
        $errors = [];
        if (empty($d['product_name'])) $errors[] = 'Product name is required.';
        return $errors;
    }
}
