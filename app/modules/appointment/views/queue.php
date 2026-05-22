<?php
/**
 * Daily Queue Dashboard View
 */
$queues = $data['queues'] ?? [];
$polyclinics = $data['polyclinics'] ?? [];
$selectedPolyId = $data['polyclinic_id'] ?? '';
?>

<div class="dashboard-container">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Antrean Hari Ini (Poliklinik)</h1>
            <p class="page-subtitle text-muted font-md">Monitor dan panggil antrean pasien rawat jalan secara real-time.</p>
        </div>
    </div>

    <!-- Polyclinic Filter -->
    <div class="card accessible-card mt-4 mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('queue') ?>" class="d-flex align-items-end gap-2 flex-wrap">
                <div class="form-group mb-0" style="flex: 1; min-width: 250px;">
                    <label for="poly_select" class="form-label font-md font-bold">Pilih Poliklinik Pelayanan</label>
                    <select id="poly_select" name="polyclinic_id" class="form-control form-control-accessible" onchange="this.form.submit()">
                        <option value="">-- Semua Poliklinik --</option>
                        <?php foreach ($polyclinics as $poly): ?>
                            <option value="<?= $poly['id'] ?>" <?= $selectedPolyId == $poly['id'] ? 'selected' : '' ?>><?= e($poly['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-accessible-lg">Tampilkan</button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Active Queue Board (Split layout for better usability) -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card text-center" style="border-top-color: var(--primary-color);">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Sedang Dipanggil</h2>
                </div>
                <div class="card-body p-4">
                    <?php
                    $activeCalling = null;
                    foreach ($queues as $q) {
                        if ($q['status'] === 'called') {
                            $activeCalling = $q;
                            break;
                        }
                    }
                    if (!$activeCalling) {
                        foreach ($queues as $q) {
                            if ($q['status'] === 'in_service') {
                                $activeCalling = $q;
                                break;
                            }
                        }
                    }
                    ?>
                    <?php if ($activeCalling): ?>
                        <div class="text-muted font-md font-bold mb-2"><?= e($activeCalling['polyclinic_name']) ?></div>
                        <div class="font-xl font-bold text-primary" style="font-size: 5rem; line-height: 1; margin: 20px 0;">
                            <?= e($activeCalling['polyclinic_code']) ?>-<?= str_pad($activeCalling['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="font-lg font-bold text-dark mb-1"><?= e($activeCalling['patient_name']) ?></div>
                        <div class="text-muted font-sm mb-4">RM: <?= e($activeCalling['medical_record_number']) ?></div>
                        <div class="badge font-bold font-sm" style="padding: 6px 12px; border-radius: 4px; background-color: #ecfeff; color: #0891b2;">
                            <?= strtoupper(e($activeCalling['status'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted p-4 font-md">Tidak ada antrean yang sedang dipanggil / dilayani saat ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Full Queue List -->
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Antrean Pasien</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-300); text-align: left; background-color: var(--gray-100);">
                                    <th class="p-3 font-bold font-md text-center" style="width: 100px;">No Antrean</th>
                                    <th class="p-3 font-bold font-md">Pasien (RM)</th>
                                    <th class="p-3 font-bold font-md">Poli & Dokter</th>
                                    <th class="p-3 font-bold font-md text-center">Prioritas</th>
                                    <th class="p-3 font-bold font-md text-center">Status</th>
                                    <th class="p-3 font-bold font-md text-center" style="width: 250px;">Aksi Panggilan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($queues)): ?>
                                    <tr>
                                        <td colspan="6" class="p-4 text-center text-muted font-md">Belum ada pasien dalam antrean hari ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($queues as $q): ?>
                                        <tr style="border-bottom: 1px solid var(--gray-200); <?= in_array($q['status'], ['called', 'in_service']) ? 'background-color: #f0fdf4;' : '' ?>">
                                            <td class="p-3 text-center">
                                                <div class="font-lg font-bold text-primary">
                                                    <?= e($q['polyclinic_code']) ?>-<?= str_pad($q['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                                                </div>
                                                <small class="text-muted font-xs"><?= date('H:i', strtotime($q['registration_time'])) ?></small>
                                            </td>
                                            <td class="p-3 font-md">
                                                <strong class="text-dark"><?= e($q['patient_name']) ?></strong>
                                                <div class="text-muted font-xs mt-1">RM: <?= e($q['medical_record_number']) ?></div>
                                            </td>
                                            <td class="p-3 font-md">
                                                <strong><?= e($q['polyclinic_name']) ?></strong>
                                                <div class="text-muted font-xs mt-1">Dr. <?= e($q['doctor_name'] ?: '-') ?></div>
                                            </td>
                                            <td class="p-3 text-center">
                                                <?php if ($q['priority'] === 'emergency'): ?>
                                                    <span class="badge" style="background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: bold;">EMERGENCY</span>
                                                <?php elseif ($q['priority'] === 'urgent'): ?>
                                                    <span class="badge" style="background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: bold;">URGENT</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background-color: #e5e7eb; color: #374151; padding: 4px 8px; border-radius: 4px;">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center font-md">
                                                <?php
                                                $statusText = strtoupper($q['status']);
                                                $style = 'background-color: #e5e7eb; color: #374151;';
                                                if ($q['status'] === 'waiting') {
                                                    $style = 'background-color: #f3f4f6; color: #4b5563;';
                                                } elseif ($q['status'] === 'called') {
                                                    $style = 'background-color: #dbeafe; color: #1e40af; font-weight: bold; border: 1px solid #bfdbfe;';
                                                } elseif ($q['status'] === 'in_service') {
                                                    $style = 'background-color: #fef3c7; color: #92400e; font-weight: bold;';
                                                } elseif ($q['status'] === 'completed') {
                                                    $style = 'background-color: #d1fae5; color: #065f46;';
                                                } elseif ($q['status'] === 'no_show') {
                                                    $style = 'background-color: #fee2e2; color: #991b1b;';
                                                }
                                                ?>
                                                <span class="badge font-sm" style="<?= $style ?> padding: 6px 12px; border-radius: 4px;">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <?php if (Auth::can('queues.call')): ?>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <?php if ($q['status'] === 'waiting'): ?>
                                                            <form action="<?= url('queue/call/' . $q['id']) ?>" method="POST" class="w-100">
                                                                <?= CSRF::getField() ?>
                                                                <input type="hidden" name="action" value="call">
                                                                <button type="submit" class="btn btn-sm btn-primary w-100">Panggil</button>
                                                            </form>
                                                        <?php elseif ($q['status'] === 'called'): ?>
                                                            <form action="<?= url('queue/call/' . $q['id']) ?>" method="POST" class="d-inline">
                                                                <?= CSRF::getField() ?>
                                                                <input type="hidden" name="action" value="call">
                                                                <button type="submit" class="btn btn-sm btn-secondary" title="Panggil Ulang">Panggil Ulang</button>
                                                            </form>
                                                            <form action="<?= url('queue/call/' . $q['id']) ?>" method="POST" class="d-inline">
                                                                <?= CSRF::getField() ?>
                                                                <input type="hidden" name="action" value="serve">
                                                                <button type="submit" class="btn btn-sm btn-success">Layani</button>
                                                            </form>
                                                            <form action="<?= url('queue/call/' . $q['id']) ?>" method="POST" class="d-inline">
                                                                <?= CSRF::getField() ?>
                                                                <input type="hidden" name="action" value="no_show">
                                                                <button type="submit" class="btn btn-sm btn-danger">Lewati</button>
                                                            </form>
                                                        <?php elseif ($q['status'] === 'in_service'): ?>
                                                            <form action="<?= url('queue/call/' . $q['id']) ?>" method="POST" class="w-100">
                                                                <?= CSRF::getField() ?>
                                                                <input type="hidden" name="action" value="complete">
                                                                <button type="submit" class="btn btn-sm btn-success w-100">Selesai</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted font-xs">-</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted font-xs">No Permission</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
