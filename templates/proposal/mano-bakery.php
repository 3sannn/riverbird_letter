<?php
/** @var array $document @var array $items @var array $company @var array $content */
$preparedFor = $content['prepared_for'] ?? ($document['business_name'] ?? '');
$body = $content['body'] ?? '';
$date = $document['document_date'] ?? '';
?>
<link rel="stylesheet" href="<?= url('/assets/css/document.css') ?>">
<div class="doc-header">
    <div class="company">
        <img src="<?= url('/assets/images/riverbird-logo.png') ?>" alt="RiverBird" style="height:42px;margin-bottom:8px">
        <h1>Project Proposal – <?= e($preparedFor) ?></h1>
        <p>Prepared For: <?= e($preparedFor) ?> · Prepared By: <?= e($content['prepared_by'] ?? 'RiverBird.in') ?> · Date: <?= e($date) ?></p>
    </div>
</div>

<div class="doc-section">
    <h2>1. Introduction</h2>
    <p><?= nl2br(e($body ?: 'RiverBird.in is pleased to offer Social Media Marketing services to enhance your online presence. Our goal is to create high-quality, engaging content that showcases your expertise.')) ?></p>
</div>

<div class="doc-section">
    <h2>2. Scope of Service</h2>
    <p><strong>A. Social Media Marketing</strong></p>
    <ul>
        <li>Content Calendar: Content Planning, Content Calendar and posting timeline.</li>
        <li>Competitor Analyse: Observing competitors’ content and performance.</li>
        <li>Content Ideation: Creating platform-specific content ideas based on trends and audience insights.</li>
        <li>Account Management: Managing and maintaining the social media account.</li>
        <li>Graphic Design: includes with Creative &amp; Promotional Posters.</li>
        <li>Keyword Optimization: Optimizing content with relevant keywords to improve reach and visibility.</li>
        <li>Brand Consistency: Maintaining a uniform brand voice and visual style.</li>
        <li>Optimization: Improving content based on performance data and trends.</li>
        <li>Posting: Posting on time &amp; Scheduling</li>
        <li>YouTube Shorts: Reels published on Instagram will also be posted as YouTube Shorts to increase content reach across platforms.</li>
        <li>Complete Management: End-to-end social media handling including planning, execution, engagement, monitoring, and reporting.</li>
    </ul>
</div>

<div class="doc-section">
    <h2>3. Service Cost</h2>
    <table class="doc-table">
        <thead><tr><th>S.No</th><th>Service</th><th class="num">Amount / Per Month</th></tr></thead>
        <tbody>
        <?php $i=1; foreach ($items as $item): if (!trim($item['description'] ?? '')) continue; ?>
            <tr><td><?= $i++ ?></td><td><?= e($item['description']) ?></td><td class="num">₹<?= e(number_format((float)($item['unit_price'] ?? $item['line_total']), 0)) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="doc-section">
    <h2>4. Deliverables</h2>
    <table class="doc-table">
        <thead><tr><th>S.no</th><th>Reels / Posters</th><th>Quantity</th></tr></thead>
        <tbody>
            <tr><td>1</td><td>Reels</td><td>8</td></tr>
            <tr><td>2</td><td>Posters</td><td>8</td></tr>
            <tr><td>3</td><td>YouTube Shorts</td><td>Same 8 Reels will be posted as Shorts</td></tr>
            <tr><td colspan="2"><strong>Total</strong></td><td>16</td></tr>
        </tbody>
    </table>
</div>

<div class="doc-section">
    <h2>5. Terms &amp; Conditions</h2>
    <?php if (!empty($document['terms'])): ?>
        <p><?= nl2br(e($document['terms'])) ?></p>
    <?php else: ?>
        <ul>
            <li>The client must provide necessary access to social media accounts, brand assets, and any other required resources before the start of the service.</li>
            <li>Delays in access provision may impact the delivery timeline, and RiverBird.in will not be responsible for such delays.</li>
            <li>RiverBird.in assures that any credentials, materials, or information shared by the client will remain strictly confidential and will not be disclosed or misused under any circumstances.</li>
            <li>The client must inform RiverBird.in at least three (3) days in advance before scheduling any video shoot.</li>
            <li>The client must pay 50% of the project value before starting with production.</li>
            <li>The remaining 50% is payable on end of each month.</li>
            <li>Any additional work beyond the agreed scope will be charged separately.</li>
            <li>Either party may terminate the contract by providing one (1) month written notice.</li>
            <li>This contract is valid for 2 months.</li>
        </ul>
    <?php endif; ?>
</div>

<div class="doc-section">
    <h2>6. Conclusion</h2>
    <p>Our Social Media Marketing will help improve visibility and generate relevant enquiries. We look forward to collaborating with you for a results-driven approach to your business.</p>
    <p style="margin-top:24px"><strong>Approval &amp; Acceptance</strong><br>I, ________________, agree to authorize RiverBird.in to proceed with the project as outlined above.</p>
</div>
