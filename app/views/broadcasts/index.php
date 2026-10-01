<?php
// app/views/broadcasts/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Internal Broadcasts & Circulars</h1>
        <p class="text-xs text-slate-500">Send instant SMS announcements, emergency meeting alerts, holiday closures, and birthday wishes.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="POST" action="<?= $baseUrl ?>/broadcasts/birthday-wishes" class="inline">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <button type="submit" class="px-3.5 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 shadow-sm flex items-center gap-1.5 transition-colors">
                <i data-lucide="cake" class="size-4 text-indigo-600"></i> Trigger Today's Birthday Wishes
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Form: Create Broadcast -->
    <div class="lg:col-span-1 rounded-xl border border-slate-200 bg-white p-6 shadow-sm h-fit">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="send" class="size-4 text-indigo-600"></i> Send New Circular / Alert
        </h3>

        <form method="POST" action="<?= $baseUrl ?>/broadcasts/send" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700">Announcement Title *</label>
                <input type="text" name="title" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs" placeholder="e.g. Urgent Staff Meeting / Holiday Notice">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Target Audience *</label>
                <select name="target_audience" id="targetAudience" onchange="toggleDeptSelect()" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white">
                    <option value="All Employees">All Active Staff (Wafanyakazi Wote)</option>
                    <option value="Department">Specific Department (Idara Maalum)</option>
                </select>
            </div>

            <div id="deptSelectBox" class="hidden">
                <label class="block text-xs font-semibold text-slate-700">Select Department *</label>
                <select name="department_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700">Message Content (SMS) *</label>
                    <button type="button" onclick="loadMeetingTemplate()" class="px-2 py-0.5 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 flex items-center gap-1 cursor-pointer">
                        <i data-lucide="sparkles" class="size-3 text-indigo-600"></i> Insert Meeting Template
                    </button>
                </div>
                <textarea id="broadcastMessage" name="message" required rows="4" class="block w-full px-3 py-2 border rounded-lg text-xs" placeholder="Type official notice text to be dispatched via SMS..."></textarea>
                <span class="text-[11px] text-slate-400 mt-1 block">Message will be dispatched instantly to registered staff mobile numbers via Beem SMS.</span>
            </div>

            <button type="submit" onclick="return confirm('Dispatch this SMS broadcast to selected employees?');" class="w-full py-2.5 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center justify-center gap-1.5 transition-colors">
                <i data-lucide="radio" class="size-4"></i> Dispatch SMS Announcement
            </button>
        </form>
    </div>

    <!-- Right Table: Broadcast History -->
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="history" class="size-4 text-indigo-600"></i> Broadcast History & Logs
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3">Title & Message</th>
                        <th class="p-3">Target</th>
                        <th class="p-3">Sender</th>
                        <th class="p-3 text-right">Date Sent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($broadcasts)): ?>
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400 text-xs">No internal broadcast circulars sent yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($broadcasts as $b): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="p-3">
                                    <span class="font-bold text-slate-900 text-xs block"><?= htmlspecialchars($b['title']) ?></span>
                                    <span class="text-xs text-slate-600 italic block mt-0.5"><?= htmlspecialchars($b['message']) ?></span>
                                </td>
                                <td class="p-3 text-xs">
                                    <?php if ($b['target_audience'] === 'Department'): ?>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                            <?= htmlspecialchars($b['department_name'] ?? 'Department') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            All Active Staff
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-xs font-medium text-slate-700"><?= htmlspecialchars($b['sender_name'] ?? 'Admin') ?></td>
                                <td class="p-3 text-xs text-slate-400 font-mono text-right"><?= format_datetime($b['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleDeptSelect() {
    const val = document.getElementById('targetAudience').value;
    const box = document.getElementById('deptSelectBox');
    if (val === 'Department') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

function loadMeetingTemplate() {
    const tomorrowStr = '<?= date('d/m/Y', strtotime('+1 day')) ?>';
    document.querySelector('input[name="title"]').value = 'Kikao cha Wafanyakazi Wote';
    document.getElementById('broadcastMessage').value = `TAARIFA: Kutakuwa na kikao cha wafanyakazi wote kesho tarehe ${tomorrowStr}, kuanzia saa 03:00 asubuhi, ukumbi wa MICO Hospital Main Hall. Tafadhali fika bila kukosa.`;
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
