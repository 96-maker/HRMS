<?php
// app/views/positions/create.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/positions" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Positions
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Add New Job Position</h2>
    <p class="text-xs text-slate-500 mb-6">Define job titles, department placement, seniority levels, and approved hiring headcount.</p>

    <form method="POST" action="<?= $baseUrl ?>/positions" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Position Title & Department Placement</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Job Title *</label>
                    <input type="text" name="title" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Senior Backend Engineer">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department *</label>
                    <select name="department_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Seniority & Headcount Structure</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Seniority Level *</label>
                    <select name="level" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Junior">Junior</option>
                        <option value="Mid">Mid</option>
                        <option value="Senior">Senior</option>
                        <option value="Lead">Lead</option>
                        <option value="Executive">Executive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Approved Headcount *</label>
                    <input type="number" name="headcount" value="4" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Open Hiring Roles *</label>
                    <input type="number" name="open_roles" value="1" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/positions" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Save Job Position
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
