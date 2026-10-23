<?php

declare(strict_types=1);

/**
 * Imports reference client + historical invoices with ORIGINAL numbers (not auto-generated).
 * Run once after migrations and admin creation: php database/seed/import_reference_data.php
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Clients\ClientRepository;
use App\Database\Connection;
use App\Documents\DocumentService;

$pdo = Connection::get();

$exists = $pdo->prepare('SELECT id FROM documents WHERE document_number = :n LIMIT 1');

$clientId = null;
$stmt = $pdo->prepare('SELECT id FROM clients WHERE business_name = :b LIMIT 1');
$stmt->execute(['b' => 'DermiiZ Skin & Hair Clinic']);
$clientId = $stmt->fetchColumn();
if (!$clientId) {
    $clientId = (new ClientRepository())->create([
        'name' => 'Dr. Shenbagaraj',
        'business_name' => 'DermiiZ Skin & Hair Clinic',
        'contact_person' => 'Dr. Shenbagaraj',
        'email' => null,
        'phone' => null,
        'address' => 'C83, 10th Cross St, West Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018.',
        'gstin' => null,
        'notes' => 'Imported from reference invoice documents in /doc',
    ]);
    echo "Created DermiiZ client (id {$clientId}).\n";
}

$adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'administrator' ORDER BY id LIMIT 1")->fetchColumn();
if (!$adminId) {
    fwrite(STDERR, "Create an administrator first.\n");
    exit(1);
}

$service = new DocumentService();

$historical = [
    [
        'document_number' => 'RB2026010',
        'title' => 'Website Development — Advance',
        'document_date' => '2026-08-27',
        'status' => 'paid',
        'amount_received' => '18000.00',
        'remarks' => 'Paid Advance - Full Project',
        'items' => [['description' => 'Website Development', 'quantity' => '1', 'unit_price' => '18000']],
        'issued_at' => '2026-08-27 00:00:00',
    ],
    [
        'document_number' => 'RB-092602',
        'title' => 'Google My Business SEO — Final',
        'document_date' => '2026-09-30',
        'status' => 'paid',
        'amount_received' => '7500.00',
        'remarks' => 'Final Payment - Full Project',
        'items' => [['description' => 'Google My Business SEO', 'quantity' => '1', 'unit_price' => '7500']],
        'issued_at' => '2026-09-30 00:00:00',
    ],
];

foreach ($historical as $inv) {
    $exists->execute(['n' => $inv['document_number']]);
    if ($exists->fetchColumn()) {
        echo "Skip {$inv['document_number']} (already imported).\n";
        continue;
    }
    $snapshot = [
        'source' => 'doc/reference',
        'document_number' => $inv['document_number'],
    ];
    $service->importHistoricalInvoice($adminId, [
        'client_id' => $clientId,
        'title' => $inv['title'],
        'document_number' => $inv['document_number'],
        'document_date' => $inv['document_date'],
        'status' => $inv['status'],
        'amount_received' => $inv['amount_received'],
        'remarks' => $inv['remarks'],
        'issued_at' => $inv['issued_at'],
        'items' => $inv['items'],
        'snapshot' => $snapshot,
    ]);
    echo "Imported {$inv['document_number']}.\n";
}

// Align sequence for Sep 2026 so the next auto invoice is RB-092603 (RB-092602 already used historically).
$pdo->prepare('INSERT INTO invoice_sequences (period_key, last_sequence) VALUES (\'0926\', 2)
    ON DUPLICATE KEY UPDATE last_sequence = GREATEST(last_sequence, 2)')->execute();

echo "Reference import complete.\n";
