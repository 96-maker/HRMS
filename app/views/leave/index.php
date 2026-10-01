<?php
// app/views/leave/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave Management</h1>
        <p class="text-xs text-slate-500">Manage statutory leave requests, approval workflows, and balance subtractions.</p>
    </div>
    <?php if (AuthService::hasPermission('leave.apply')): ?>
        <a href="<?= $baseUrl ?>/leave/apply" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-1.5">
            <i data-lucide="plus" class="size-4"></i> Apply for Leave
        </a>
    <?php endif; ?>
</div>

<!-- Leave Requests Table -->
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Leave Type</th>
                    <th class="p-3.5">Dates</th>
                    <th class="p-3.5">Days</th>
                    <th class="p-3.5">Status</th>
                    <th class="p-3.5 text-center" colSpan="3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($leaveRequests)): ?>
                    <tr>
                        <td colSpan="8" class="p-8 text-center text-slate-400 text-xs">No leave applications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaveRequests as $req): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                        <?= get_initials($req['first_name'] . ' ' . $req['last_name']) ?>
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></span>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($req['department_name'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 text-xs font-semibold text-slate-800"><?= htmlspecialchars($req['leave_type_name']) ?></td>
                            <td class="p-3.5 text-xs text-slate-500 font-mono"><?= format_date($req['start_date']) ?> - <?= format_date($req['end_date']) ?></td>
                            <td class="p-3.5 text-xs font-mono font-semibold text-slate-900"><?= $req['total_days'] ?> Days</td>
                            <td class="p-3.5">
                                 <?php 
                                     $st = $req['status'];
                                     $label = $st;
                                     if ($st === 'Completed') {
                                         $label = 'Duty Resumed';
                                         $bClass = 'bg-blue-50 text-blue-700 ring-blue-600/20';
                                     } elseif ($st === 'Approved') {
                                         $label = 'Approved';
                                         $bClass = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
                                     } elseif ($st === 'Pending') {
                                         $bClass = 'bg-amber-50 text-amber-700 ring-amber-600/20';
                                     } else {
                                         $bClass = 'bg-rose-50 text-rose-700 ring-rose-600/20';
                                     }
                                 ?>
                                 <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset <?= $bClass ?>">
                                     <?= htmlspecialchars($label) ?>
                                 </span>
                             </td>
                             <!-- Column 1: Form PDF -->
                             <td class="p-3.5 text-center">
                                 <a href="<?= $baseUrl ?>/leave/print-form?id=<?= $req['id'] ?>" target="_blank" title="View & Download PDF Form" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors shadow-sm">
                                     <i data-lucide="file-text" class="size-3.5"></i> Leave Form
                                 </a>
                             </td>
                             <!-- Column 2: Status & Approval Action -->
                             <td class="p-3.5 text-center">
                                 <?php if ($st === 'Pending' && AuthService::hasPermission('leave.approve')): ?>
                                     <form method="POST" action="<?= $baseUrl ?>/leave/approve" class="inline">
                                         <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                         <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                         <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors shadow-sm cursor-pointer">
                                             <i data-lucide="check-circle-2" class="size-3.5"></i> Approve
                                         </button>
                                     </form>
                                 <?php elseif ($st === 'Approved'): ?>
                                     <span class="text-xs font-semibold text-emerald-600 flex items-center justify-center gap-1"><i data-lucide="check" class="size-3.5"></i> Approved</span>
                                 <?php elseif ($st === 'Completed'): ?>
                                     <span class="text-xs font-semibold text-blue-600 flex items-center justify-center gap-1"><i data-lucide="user-check" class="size-3.5"></i> Duty Resumed</span>
                                 <?php else: ?>
                                     <span class="text-xs text-slate-400">-</span>
                                 <?php endif; ?>
                             </td>
                            <!-- Column 3: Reject -->
                            <td class="p-3.5 text-center">
                                <?php if ($st === 'Pending' && AuthService::hasPermission('leave.approve')): ?>
                                    <form method="POST" action="<?= $baseUrl ?>/leave/reject" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors shadow-sm">
                                            <i data-lucide="x-circle" class="size-3.5"></i> Reject
                                        </button>
                                    </form>
                                <?php elseif ($st === 'Rejected'): ?>
                                    <span class="text-[11px] font-semibold text-rose-600 flex items-center justify-center gap-1"><i data-lucide="x" class="size-3"></i> Rejected</span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
