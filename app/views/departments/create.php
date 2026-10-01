<?php
// app/views/departments/create.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/departments" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Departments
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Create New Department</h2>
    <p class="text-xs text-slate-500 mb-6">Define organizational unit structure, department head, and annual budget allocation.</p>

    <form method="POST" action="<?= $baseUrl ?>/departments" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Department Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department Name *</label>
                    <input type="text" name="name" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Research & Development">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department Code *</label>
                    <input type="text" name="code" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono uppercase focus:ring-2 focus:ring-indigo-500" placeholder="RND-06">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Management & Financial Allocation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department Head</label>
                    <select name="manager_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Manager</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Annual Budget (TZS) *</label>
                    <input type="number" name="budget" value="120000000" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/departments" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Save & Create Department
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
