<?php

declare(strict_types=1);

/**
 * Optional seed: templates + reference client/historical documents.
 * Does NOT run automatic invoice numbering for historical records.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Database\Connection;

$pdo = Connection::get();

$tplCount = (int) $pdo->query('SELECT COUNT(*) FROM templates')->fetchColumn();
if ($tplCount > 0) {
    echo "Templates already seeded.\n";
    exit(0);
}

$templates = [
    [
        'slug' => 'invoice-default',
        'document_type' => 'invoice',
        'name' => 'Standard Invoice',
        'layout_view' => 'invoice/default',
        'default_content' => json_encode([
            'show_ship_to' => true,
            'show_discount' => true,
            'show_shipping' => true,
        ]),
    ],
    [
        'slug' => 'proposal-mano-bakery',
        'document_type' => 'proposal',
        'name' => 'Social Media Marketing Proposal',
        'layout_view' => 'proposal/mano-bakery',
        'default_content' => json_encode([
            'introduction' => 'RiverBird.in is pleased to offer Social Media Marketing services to enhance your online presence.',
            'contract_valid_months' => 2,
        ]),
    ],
    [
        'slug' => 'quotation-default',
        'document_type' => 'quotation',
        'name' => 'Standard Quotation',
        'layout_view' => 'quotation/default',
        'default_content' => json_encode([]),
    ],
    [
        'slug' => 'letter-default',
        'document_type' => 'letter',
        'name' => 'Business Letter',
        'layout_view' => 'letter/default',
        'default_content' => json_encode([]),
    ],
];

$stmt = $pdo->prepare(
    'INSERT INTO templates (slug, document_type, name, layout_view, default_content, is_system)
     VALUES (:slug, :document_type, :name, :layout_view, :default_content, 1)'
);

foreach ($templates as $t) {
    $stmt->execute($t);
}

echo "Templates seeded.\n";
