<?php

class DashboardController extends Controller {

    private const VIEWS = [
        'active'   => 'Active Licenses',
        'expiring' => 'Licenses Expiring Within 30 Days',
        'expired'  => 'Expired Licenses',
    ];

    public function index(): void {
        $this->requireAuth();
        $view = $_GET['view'] ?? 'expiring';
        if (!isset(self::VIEWS[$view])) $view = 'expiring';

        $licenseModel = new LicenseModel();
        $stats        = $licenseModel->getStatCounts();
        $licenses     = $licenseModel->getDashboardSegment($view, 30);

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
            'views'     => self::VIEWS,
            'byProduct' => $byProduct,
            'amc'       => $amc,
        ]);
    }
}
