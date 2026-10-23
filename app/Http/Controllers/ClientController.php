<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Audit\AuditLogger;
use App\Auth\Auth;
use App\Clients\ClientRepository;
use App\Support\Csrf;
use App\Support\View;

final class ClientController
{
    public function index(): void
    {
        Auth::requireLogin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $archived = isset($_GET['archived']);
        $repo = new ClientRepository();
        $result = $repo->paginate($page, 20, $search ?: null, $archived);

        View::render('clients/index', [
            'user' => Auth::user(),
            'clients' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'search' => $search,
            'archived' => $archived,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        View::render('clients/form', ['user' => Auth::user(), 'client' => null]);
    }

    public function store(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $data = $this->validatedClient($_POST);
        $id = (new ClientRepository())->create($data);
        AuditLogger::log(Auth::user()['id'], 'client_created', 'client', $id);
        flash('success', 'Client created.');
        redirect('/clients');
    }

    public function edit(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        $id = (int) ($_GET['id'] ?? 0);
        $client = (new ClientRepository())->find($id);
        if (!$client) {
            redirect('/clients');
        }
        View::render('clients/form', ['user' => Auth::user(), 'client' => $client]);
    }

    public function update(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->validatedClient($_POST);
        (new ClientRepository())->update($id, $data);
        AuditLogger::log(Auth::user()['id'], 'client_updated', 'client', $id);
        flash('success', 'Client updated.');
        redirect('/clients');
    }

    public function archive(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        (new ClientRepository())->archive($id);
        AuditLogger::log(Auth::user()['id'], 'client_archived', 'client', $id);
        flash('success', 'Client archived.');
        redirect('/clients');
    }

    private function validatedClient(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Client name is required.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/clients');
        }

        return [
            'name' => $name,
            'business_name' => trim((string) ($input['business_name'] ?? '')) ?: null,
            'contact_person' => trim((string) ($input['contact_person'] ?? '')) ?: null,
            'email' => trim((string) ($input['email'] ?? '')) ?: null,
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'address' => trim((string) ($input['address'] ?? '')) ?: null,
            'gstin' => trim((string) ($input['gstin'] ?? '')) ?: null,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ];
    }
}
