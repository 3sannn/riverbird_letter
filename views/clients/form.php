<h1><?= $client ? 'Edit client' : 'New client' ?></h1>
<form method="post" action="<?= $client ? url('/clients/update') : url('/clients') ?>" class="card form-grid">
    <?= \App\Support\Csrf::field() ?>
    <?php if ($client): ?><input type="hidden" name="id" value="<?= (int) $client['id'] ?>"><?php endif; ?>
    <div class="form-row">
        <div><label>Client name *</label><input name="name" required value="<?= e($client['name'] ?? '') ?>"></div>
        <div><label>Business / organisation</label><input name="business_name" value="<?= e($client['business_name'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div><label>Contact person</label><input name="contact_person" value="<?= e($client['contact_person'] ?? '') ?>"></div>
        <div><label>Email</label><input type="email" name="email" value="<?= e($client['email'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div><label>Phone</label><input name="phone" value="<?= e($client['phone'] ?? '') ?>"></div>
        <div><label>GSTIN</label><input name="gstin" value="<?= e($client['gstin'] ?? '') ?>"></div>
    </div>
    <div><label>Address</label><textarea name="address"><?= e($client['address'] ?? '') ?></textarea></div>
    <div><label>Notes</label><textarea name="notes"><?= e($client['notes'] ?? '') ?></textarea></div>
    <div class="toolbar">
        <button type="submit" class="btn btn-primary">Save</button>
        <a class="btn" href="<?= url('/clients') ?>">Cancel</a>
    </div>
</form>
