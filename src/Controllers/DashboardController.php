<?php

class DashboardController extends Controller {

    private const VIEWS = [
        'active'   => 'Active Licenses',
        'expiring' => 'Licenses Expiring Within 30 Days',
        'expired'  => 'Expired Licenses',
        'customers' => 'All Customers',
        'amc'      => 'Total AMC Revenue - Customers',
    ];

    public function index(): void {
        $this->requireAuth();
        $view = $_GET['view'] ?? 'expiring';
        $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
        if (!isset(self::VIEWS[$view]) || ($view === 'amc' && !$isAdmin)) $view = 'expiring';
        $views = self::VIEWS;
        if (!$isAdmin) unset($views['amc']);

        $licenseModel = new LicenseModel();
        $stats        = $licenseModel->getStatCounts();
        $isLicenseView = !in_array($view, ['customers', 'amc'], true);
        $licenses     = $isLicenseView ? $licenseModel->getDashboardSegment($view, 30) : [];

        // Customer list for the Total Customers / Total AMC Revenue cards
        $customerRows = $view === 'amc' ? $licenseModel->getAmcRevenueByCustomer()
                      : ($view === 'customers' ? $licenseModel->getCustomersSummary() : []);

        // Chart data for the selected segment
        $byProduct = [];
        $amc       = ['active' => 0, 'expired' => 0, 'not_applicable' => 0];
        foreach ($licenses as $lic) {
            $byProduct[$lic['product_name']] = ($byProduct[$lic['product_name']] ?? 0) + 1;
            if (isset($amc[$lic['amc_status']])) $amc[$lic['amc_status']]++;
        }
        arsort($byProduct);


        $this->render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'stats'     => $stats,
            'licenses'  => $licenses,
            'view'      => $view,
            'views'     => $views,
            'byProduct' => $byProduct,
            'amc'       => $amc,
            'customerRows' => $customerRows,
        ]);
    }
}
