<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\Auth;
use App\Clients\ClientRepository;
use App\Database\Connection;
use App\Documents\DocumentRepository;
use App\Documents\DocumentService;
use App\Files\FileStorage;
use App\Payments\PaymentRepository;
use App\Pdf\PdfGenerator;
use App\Support\Csrf;
use App\Support\View;
use App\Templates\TemplateRenderer;

final class DocumentController
{
    public function index(): void
    {
        Auth::requireLogin();
        $filters = [
            'type' => $_GET['type'] ?? null,
            'status' => $_GET['status'] ?? null,
            'client_id' => $_GET['client_id'] ?? null,
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $repo = new DocumentRepository();
        $result = $repo->paginate($filters, $page);

        View::render('documents/index', [
            'user' => Auth::user(),
            'documents' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'clients' => (new ClientRepository())->allActive(),
            'page' => $page,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        $type = $_GET['type'] ?? 'invoice';
        View::render('documents/editor', [
            'user' => Auth::user(),
            'document' => null,
            'items' => [['description' => '', 'quantity' => '1', 'unit_price' => '0']],
            'clients' => (new ClientRepository())->allActive(),
            'document_type' => $type,
            'templates' => $this->templatesForType($type),
        ], 'layouts/document-editor');
    }

    public function edit(): void
    {
        Auth::requireRole(['administrator', 'staff', 'viewer']);
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new DocumentRepository();
        $document = $repo->find($id);
        if (!$document) {
            redirect('/documents');
        }
        if (Auth::user()['role'] === 'viewer') {
            redirect('/documents/show?id=' . $id);
        }

        $items = $repo->getItems($id);
        if (!$items) {
            $items = [['description' => '', 'quantity' => '1', 'unit_price' => '0']];
        }

        View::render('documents/editor', [
            'user' => Auth::user(),
            'document' => $document,
            'items' => $items,
            'clients' => (new ClientRepository())->allActive(),
            'document_type' => $document['document_type'],
            'templates' => $this->templatesForType($document['document_type']),
            'payments' => (new PaymentRepository())->forDocument($id),
        ], 'layouts/document-editor');
    }

    public function show(): void
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new DocumentRepository();
        $document = $repo->find($id);
        if (!$document) {
            redirect('/documents');
        }
        View::render('documents/show', [
            'user' => Auth::user(),
            'document' => $document,
            'items' => $repo->getItems($id),
            'payments' => (new PaymentRepository())->forDocument($id),
            'preview_html' => $this->renderPreview($document, $repo->getItems($id)),
        ]);
    }

    public function save(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);

        $payload = $this->payloadFromRequest($_POST);
        $documentId = !empty($_POST['id']) ? (int) $_POST['id'] : null;

        try {
            $id = (new DocumentService())->saveDraft((int) Auth::user()['id'], $payload, $documentId);
            if (!empty($_FILES['attachment']['name'])) {
                (new FileStorage())->storeUpload((int) Auth::user()['id'], $id, $_FILES['attachment']);
            }
            if (!empty($_POST['ajax'])) {
                json_response(['ok' => true, 'id' => $id]);
            }
            flash('success', 'Draft saved.');
            redirect('/documents/edit?id=' . $id);
        } catch (\Throwable $e) {
            if (!empty($_POST['ajax'])) {
                json_response(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            flash('error', $e->getMessage());
            redirect($_SERVER['HTTP_REFERER'] ?? '/documents');
        }
    }

    public function issue(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        try {
            (new DocumentService())->issueInvoice((int) Auth::user()['id'], $id);
            flash('success', 'Invoice issued.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/documents/show?id=' . $id);
    }

    public function duplicate(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $newId = (new DocumentService())->duplicate((int) Auth::user()['id'], $id);
        redirect('/documents/edit?id=' . $newId);
    }

    public function addPayment(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['document_id'] ?? 0);
        try {
            (new PaymentRepository())->add((int) Auth::user()['id'], $id, [
                'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
                'amount' => $_POST['amount'] ?? '0',
                'payment_method' => $_POST['payment_method'] ?? null,
                'reference' => $_POST['reference'] ?? null,
                'remarks' => $_POST['remarks'] ?? null,
            ]);
            flash('success', 'Payment recorded.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/documents/show?id=' . $id);
    }

    public function pdf(): void
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new DocumentRepository();
        $document = $repo->find($id);
        if (!$document) {
            http_response_code(404);
            exit;
        }
        $items = $repo->getItems($id);
        $html = $this->renderPreview($document, $items, true);
        $pdf = (new PdfGenerator())->fromHtml($html);
        $filename = ($document['document_number'] ?: 'draft-' . $id) . '.pdf';
        (new FileStorage())->storeGeneratedPdf((int) Auth::user()['id'], $id, $pdf, $filename);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
        exit;
    }

    public function archive(): void
    {
        Auth::requireRole(['administrator', 'staff']);
        Csrf::validate($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        Connection::get()->prepare('UPDATE documents SET is_archived = 1 WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Document archived.');
        redirect('/documents');
    }

    private function payloadFromRequest(array $post): array
    {
        $items = [];
        $descriptions = $post['item_description'] ?? [];
        $quantities = $post['item_quantity'] ?? [];
        $prices = $post['item_unit_price'] ?? [];
        foreach ($descriptions as $i => $desc) {
            $items[] = [
                'description' => $desc,
                'quantity' => $quantities[$i] ?? '1',
                'unit_price' => $prices[$i] ?? '0',
            ];
        }

        return [
            'client_id' => $post['client_id'] ?? null,
            'template_id' => $post['template_id'] ?? null,
            'document_type' => $post['document_type'] ?? 'invoice',
            'title' => trim((string) ($post['title'] ?? 'Untitled')),
            'document_date' => $post['document_date'] ?? null,
            'due_date' => $post['due_date'] ?? null,
            'discount_amount' => $post['discount_amount'] ?? '0',
            'tax_rate' => $post['tax_rate'] ?? '0',
            'shipping_amount' => $post['shipping_amount'] ?? '0',
            'remarks' => $post['remarks'] ?? null,
            'notes' => $post['notes'] ?? null,
            'terms' => $post['terms'] ?? null,
            'content' => [
                'body' => $post['content_body'] ?? '',
                'prepared_for' => $post['prepared_for'] ?? '',
                'prepared_by' => $post['prepared_by'] ?? 'RiverBird.in',
                'sections' => $post['content_sections'] ?? [],
            ],
            'items' => $items,
        ];
    }

    private function templatesForType(string $type): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT id, name, layout_view FROM templates WHERE document_type = :type ORDER BY name'
        );
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function renderPreview(array $document, array $items, bool $forPdf = false): string
    {
        $layout = 'invoice/default';
        if ($document['template_id']) {
            $stmt = Connection::get()->prepare('SELECT layout_view FROM templates WHERE id = :id');
            $stmt->execute(['id' => $document['template_id']]);
            $layout = $stmt->fetchColumn() ?: $layout;
        } elseif ($document['document_type'] === 'proposal') {
            $layout = 'proposal/mano-bakery';
        } elseif ($document['document_type'] === 'quotation') {
            $layout = 'quotation/default';
        } elseif ($document['document_type'] === 'letter') {
            $layout = 'letter/default';
        }

        $content = json_decode($document['content_json'] ?? '{}', true) ?: [];
        return (new TemplateRenderer())->render($layout, [
            'document' => $document,
            'items' => $items,
            'content' => $content,
            'company' => config('company'),
            'for_pdf' => $forPdf,
        ]);
    }
}
