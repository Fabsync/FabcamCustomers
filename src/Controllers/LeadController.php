<?php

class LeadController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $filters = [
            'status'   => $_GET['status']   ?? '',
            'interest' => $_GET['interest'] ?? '',
            'q'        => trim($_GET['q'] ?? ''),
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $model = new LeadModel();
        $total = $model->getCount($filters);

        $this->render('leads/index', [
            'pageTitle'  => 'Leads & Enquiries',
            'leads'      => $model->getPaginated($filters, ($page - 1) * $perPage, $perPage),
            'summary'    => $model->getStatusSummary(),
            'filters'    => $filters,
            'page'       => $page,
            'totalPages' => (int) ceil($total / $perPage),
            'total'      => $total,
            'perPage'    => $perPage,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $lead = [
            'status'         => 'new',
            'interest_level' => 'warm',
            'source'         => 'phone',
            'assigned_to'    => $_SESSION['user_id'],
            'next_follow_up' => date('Y-m-d', strtotime('+3 days')),
        ];
        // Pre-fill from an existing customer when coming from the customer page
        $custId = (int)($_GET['customer_id'] ?? 0);
        if ($custId && ($c = (new CustomerModel())->findById($custId))) {
            $lead += [
                'customer_id'    => $c['id'],
                'company_name'   => $c['company_name'],
                'contact_person' => $c['contact_person'],
                'mobile'         => $c['mobile'],
                'email'          => $c['email'],
            ];
            $lead['source'] = 'existing_customer';
        }
        $this->renderForm('New Lead', $lead, []);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        [$d, $errors] = $this->parsePost();
        if ($errors) { $this->renderForm('New Lead', $d, $errors); return; }

        $model = new LeadModel();
        $id    = $model->insert($d, $_SESSION['user_id']);

        // Opening discussion notes become the first entry of the discussion log
        $notes = trim($_POST['initial_notes'] ?? '');
        if ($notes !== '') {
            $lead = $model->findById($id);
            $model->addActivity($lead, [
                'activity_date'  => date('Y-m-d'),
                'activity_type'  => 'note',
                'notes'          => $notes,
                'status'         => $lead['status'],
                'interest_level' => $lead['interest_level'],
                'next_follow_up' => $lead['next_follow_up'],
                'close_reason'   => $lead['close_reason'],
            ], $_SESSION['user_id']);
        }

        $this->flash('success', 'Lead created successfully.');
        $this->redirect('/leads/view/' . $id);
    }

    public function edit(string $id): void {
        $this->requireAuth();
        $lead = (new LeadModel())->findById((int)$id);
        if (!$lead) { $this->notFound(); return; }
        $this->renderForm('Edit ' . $lead['lead_number'], $lead, []);
    }

    public function update(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $model = new LeadModel();
        $lead  = $model->findById((int)$id);
        if (!$lead) { $this->notFound(); return; }

        [$d, $errors] = $this->parsePost();
        if ($errors) {
            $this->renderForm('Edit ' . $lead['lead_number'], array_merge($lead, $d), $errors);
            return;
        }

        $model->update((int)$id, $d, $lead);
        $this->flash('success', 'Lead updated.');
        $this->redirect('/leads/view/' . $id);
    }

    public function view(string $id): void {
        $this->requireAuth();
        $model = new LeadModel();
        $lead  = $model->findById((int)$id);
        if (!$lead) { $this->notFound(); return; }

        $this->render('leads/view', [
            'pageTitle'  => $lead['lead_number'] . ' — ' . $lead['company_name'],
            'lead'       => $lead,
            'activities' => $model->getActivities((int)$id),
        ]);
    }

    /** Log a discussion held with the account and update status / interest in one step. */
    public function addActivity(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $model = new LeadModel();
        $lead  = $model->findById((int)$id);
        if (!$lead) { $this->notFound(); return; }

        $a = [
            'activity_date'  => $_POST['activity_date'] ?? '',
            'activity_type'  => $_POST['activity_type'] ?? 'call',
            'notes'          => trim($_POST['notes'] ?? ''),
            'status'         => $_POST['status'] ?? $lead['status'],
            'interest_level' => $_POST['interest_level'] ?? $lead['interest_level'],
            'next_follow_up' => trim($_POST['next_follow_up'] ?? ''),
            'close_reason'   => trim($_POST['close_reason'] ?? ''),
        ];
        if (!isset(LeadModel::ACTIVITY_TYPES[$a['activity_type']])) $a['activity_type'] = 'call';
        if (!isset(LeadModel::STATUSES[$a['status']]))              $a['status'] = $lead['status'];
        if (!isset(LeadModel::INTEREST_LEVELS[$a['interest_level']])) $a['interest_level'] = $lead['interest_level'];
        if (!$this->isDate($a['activity_date']))                    $a['activity_date'] = date('Y-m-d');
        if ($a['next_follow_up'] !== '' && !$this->isDate($a['next_follow_up'])) $a['next_follow_up'] = '';
        if (in_array($a['status'], LeadModel::CLOSED_STATUSES, true)) $a['next_follow_up'] = '';

        if ($a['notes'] === '') {
            $this->flash('danger', 'Please enter the discussion notes.');
            $this->redirect('/leads/view/' . $id);
            return;
        }

        $model->addActivity($lead, $a, $_SESSION['user_id']);

        $msg = 'Discussion logged.';
        if ($a['status'] !== $lead['status']) {
            $msg .= ' Status changed to ' . LeadModel::STATUSES[$a['status']] . '.';
        }
        $this->flash('success', $msg);
        $this->redirect('/leads/view/' . $id);
    }

    public function deleteActivity(string $id, string $activityId): void {
        $this->requireAuth();
        $this->requireRole('admin');
        $this->validateCsrf();
        (new LeadModel())->deleteActivity((int)$id, (int)$activityId);
        $this->flash('success', 'Discussion entry deleted.');
        $this->redirect('/leads/view/' . $id);
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->requireRole('admin');
        $this->validateCsrf();
        (new LeadModel())->delete((int)$id);
        $this->flash('success', 'Lead and its discussion history deleted.');
        $this->redirect('/leads');
    }

    private function renderForm(string $title, array $lead, array $errors): void {
        $this->render('leads/form', [
            'pageTitle' => $title,
            'lead'      => $lead,
            'customers' => (new CustomerModel())->getAll(),
            'products'  => (new ProductModel())->getAll(),
            'users'     => array_filter((new UserModel())->getAll(), fn($u) => (int)$u['is_active'] === 1),
            'errors'    => $errors,
        ]);
    }

    private function parsePost(): array {
        $value = trim($_POST['expected_value'] ?? '');
        $d = [
            'customer_id'    => (int)($_POST['customer_id'] ?? 0),
            'company_name'   => trim($_POST['company_name']   ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'mobile'         => trim($_POST['mobile']         ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'product_id'     => (int)($_POST['product_id']    ?? 0),
            'requirement'    => trim($_POST['requirement']    ?? ''),
            'source'         => $_POST['source']         ?? 'phone',
            'status'         => $_POST['status']         ?? 'new',
            'interest_level' => $_POST['interest_level'] ?? 'warm',
            'expected_value' => $value === '' ? null : max(0, (float)$value),
            'next_follow_up' => trim($_POST['next_follow_up'] ?? ''),
            'close_reason'   => trim($_POST['close_reason']   ?? ''),
            'assigned_to'    => (int)($_POST['assigned_to']   ?? 0),
        ];

        if (!isset(LeadModel::SOURCES[$d['source']]))                $d['source'] = 'other';
        if (!isset(LeadModel::STATUSES[$d['status']]))               $d['status'] = 'new';
        if (!isset(LeadModel::INTEREST_LEVELS[$d['interest_level']])) $d['interest_level'] = 'warm';
        if ($d['next_follow_up'] !== '' && !$this->isDate($d['next_follow_up'])) $d['next_follow_up'] = '';
        // A closed lead has nothing left to follow up on
        if (in_array($d['status'], LeadModel::CLOSED_STATUSES, true)) $d['next_follow_up'] = '';
        else $d['close_reason'] = '';

        $errors = [];
        if ($d['company_name'] === '') $errors[] = 'Company name is required.';
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        return [$d, $errors];
    }

    private function isDate(string $s): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $s);
        return $dt && $dt->format('Y-m-d') === $s;
    }

    private function notFound(): void {
        http_response_code(404);
        $this->render('errors/404', ['pageTitle' => 'Not Found']);
    }
}
