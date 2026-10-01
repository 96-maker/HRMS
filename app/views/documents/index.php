<?php
// app/views/documents/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();

$expiringCount = 0;
foreach ($documents as $d) {
    if (!empty($d['expiry_date'])) {
        $days = (strtotime($d['expiry_date']) - time()) / 86400;
        if ($days <= 30) $expiringCount++;
    }
}
?>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Document Repository</h1>
        <p class="text-xs text-slate-500">Employment contracts, work permits, NIDA cards, academic certificates, and contract expiration alerts.</p>
    </div>
    <?php if (AuthService::hasPermission('documents.upload')): ?>
        <div>
            <a href="<?= $baseUrl ?>/documents/upload" class="px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-colors cursor-pointer">
                <i data-lucide="upload" class="size-4"></i> Upload Document
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($expiringCount > 0): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-6 text-amber-800 flex items-center gap-3 text-xs">
        <i data-lucide="alert-triangle" class="size-5 shrink-0 text-amber-600"></i>
        <span><strong><?= $expiringCount ?> Document Warning(s):</strong> Contracts or work permits expiring within 30 days are highlighted in amber.</span>
    </div>
<?php endif; ?>

<!-- Search and Category Filters -->
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs mb-6">
    <form method="GET" action="<?= $baseUrl ?>/documents" class="flex flex-col md:flex-row gap-3">
        <div class="flex-1 relative">
            <i data-lucide="search" class="size-4 absolute left-3 top-3 text-slate-400"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search document title or employee..." class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div class="w-full md:w-56">
            <select name="type" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="all">All Categories</option>
                <option value="Contracts" <?= $typeFilter === 'Contracts' ? 'selected' : '' ?>>Contracts (Mikataba)</option>
                <option value="Work Permits" <?= $typeFilter === 'Work Permits' ? 'selected' : '' ?>>Work Permits (Vibali vya Kazi)</option>
                <option value="IDs & Passports" <?= $typeFilter === 'IDs & Passports' ? 'selected' : '' ?>>IDs & Passports (NIDA/Kitambulisho)</option>
                <option value="Certifications" <?= $typeFilter === 'Certifications' ? 'selected' : '' ?>>Certifications (Vyeti vya Elimu)</option>
                <option value="Other" <?= $typeFilter === 'Other' ? 'selected' : '' ?>>Other Official Documents</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-slate-800 rounded-xl hover:bg-slate-900 transition-colors cursor-pointer">Filter</button>
            <?php if (!empty($search) || !empty($typeFilter)): ?>
                <a href="<?= $baseUrl ?>/documents" class="px-3 py-2 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Documents Data Table -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Document Title</th>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Category</th>
                    <th class="p-3.5">File Size</th>
                    <th class="p-3.5">Expiry Date</th>
                    <th class="p-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                            <i data-lucide="folder-open" class="size-8 mx-auto mb-2 text-slate-300"></i>
                            No documents found in repository matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <?php 
                            $isExpiring = false;
                            if (!empty($doc['expiry_date'])) {
                                $daysLeft = (strtotime($doc['expiry_date']) - time()) / 86400;
                                if ($daysLeft <= 30) $isExpiring = true;
                            }
                            // Build download URL using base_url
                            $downloadUrl = $baseUrl . '/public/uploads/' . $doc['stored_name'];
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors <?= $isExpiring ? 'bg-amber-50/60' : '' ?>">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                                        <i data-lucide="file-text" class="size-4"></i>
                                    </div>
                                    <span class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($doc['title']) ?></span>
                                </div>
                            </td>
                            <td class="p-3.5 text-xs font-semibold text-slate-700"><?= htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) ?></td>
                            <td class="p-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    <?= htmlspecialchars($doc['document_type']) ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-xs font-mono text-slate-500"><?= round($doc['file_size'] / 1024, 0) ?> KB</td>
                            <td class="p-3.5 text-xs font-mono <?= $isExpiring ? 'font-bold text-amber-700' : 'text-slate-500' ?>">
                                <?= !empty($doc['expiry_date']) ? format_date($doc['expiry_date']) : '<span class="text-slate-300">N/A</span>' ?>
                            </td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= $baseUrl ?>/documents/download?id=<?= $doc['id'] ?>" target="_blank" class="p-1.5 rounded-lg text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors border border-slate-200" title="Download Document">
                                        <i data-lucide="download" class="size-4"></i>
                                    </a>
                                    <?php if (AuthService::hasPermission('documents.delete')): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/documents/delete" onsubmit="return confirm('Futa hati hii? Action hii hairejeleki.');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 border border-slate-200 transition-colors" title="Delete Document"><i data-lucide="trash-2" class="size-4"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadDocModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="upload-cloud" class="size-5 text-indigo-600"></i> Upload Employee Document
            </h3>
            <button type="button" onclick="closeUploadModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="size-5"></i>
            </button>
        </div>

        <form method="POST" action="<?= $baseUrl ?>/documents/upload" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Target Employee *</label>
                <select name="employee_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Document Category *</label>
                <select name="document_type" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="Contracts">Contracts (Mikataba ya Kazi)</option>
                    <option value="Work Permits">Work Permits (Vibali vya Kazi)</option>
                    <option value="IDs & Passports">IDs & Passports (NIDA/Kitambulisho)</option>
                    <option value="Certifications">Certifications (Vyeti vya Elimu)</option>
                    <option value="Other">Other Official Documents</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Document Title / Display Name *</label>
                <input type="text" name="title" required placeholder="e.g. Employment Contract 2026 / NIDA Copy" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <span class="text-[11px] text-slate-400 mt-1 block">Leave empty if document has no expiration.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select File (PDF, PNG, JPG, DOCX - Max 5MB) *</label>
                <input type="file" name="file" required class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl cursor-pointer">
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 mt-6">
                <button type="button" onclick="closeUploadModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
                    <i data-lucide="upload" class="size-4"></i> Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal() {
    document.getElementById('uploadDocModal').classList.remove('hidden');
}
function closeUploadModal() {
    document.getElementById('uploadDocModal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
