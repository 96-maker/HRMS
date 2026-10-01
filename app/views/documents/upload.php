<?php
// app/views/documents/upload.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/documents" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Repository
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Upload Employee Document</h2>
    <p class="text-xs text-slate-500 mb-6">Attach official employment contracts, work permits, NIDA cards, or certifications to employee profile.</p>

    <form method="POST" action="<?= $baseUrl ?>/documents/upload" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Target Employee & Classification -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Target Employee & Classification</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Target Employee *</label>
                    <select name="employee_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Document Category *</label>
                    <select name="document_type" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Contracts">Contracts (Mikataba ya Kazi)</option>
                        <option value="Work Permits">Work Permits (Vibali vya Kazi)</option>
                        <option value="IDs & Passports">IDs & Passports (NIDA/Kitambulisho)</option>
                        <option value="Certifications">Certifications (Vyeti vya Elimu)</option>
                        <option value="Other">Other Official Documents</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Document Metadata & Details -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Document Details & Validity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Document Title / Display Name *</label>
                    <input type="text" name="title" required placeholder="e.g. Employment Contract 2026 / NIDA Copy" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                    <span class="text-[11px] text-slate-400 mt-1 block">Leave empty if the document has no expiration date.</span>
                </div>
            </div>
        </div>

        <!-- Section 3: File Attachment -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">3. File Attachment</h3>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select File (PDF, PNG, JPG, DOCX - Max 5MB) *</label>
                <input type="file" name="file" required class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border rounded-lg cursor-pointer">
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/documents" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
                <i data-lucide="upload" class="size-4"></i> Save & Upload Document
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
