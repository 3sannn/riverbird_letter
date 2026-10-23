<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Auth;
use App\Clients\ClientRepository;
use App\Documents\DocumentRepository;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        if (!Auth::hasUsers()) {
            redirect('/setup');
        }
        Auth::requireLogin();

        $docRepo = new DocumentRepository();
        $stats = $docRepo->dashboardStats();
        $stats['total_clients'] = (new ClientRepository())->countActive();

        View::render('dashboard/index', [
            'user' => Auth::user(),
            'stats' => $stats,
            'recent_documents' => $docRepo->recentDocuments(),
            'recent_activity' => $docRepo->recentActivity(),
        ]);
    }
}
