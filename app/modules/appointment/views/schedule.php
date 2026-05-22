<?php
/**
 * View Doctor Schedule Page
 */
$schedules = $data['schedules'] ?? [];
$doctors = $data['doctors'] ?? [];
$polyclinics = $data['polyclinics'] ?? [];
$rooms = $data['rooms'] ?? [];

$days = [
    'monday' => 'Senin',
    'tuesday' => 'Selasa',
    'wednesday' => 'Rabu',
    'thursday' => 'Kamis',
    'friday' => 'Jumat',
    'saturday' => 'Sabtu',
    'sunday' => 'Minggu'
];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Jadwal Dokter Spesialis</h1>
            <p class="page-subtitle text-muted font-md">Atur jadwal praktek harian, ruangan, dan kuota pasien untuk setiap dokter spesialis.</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Left Column: Schedules List -->
        <div class="col-lg-8 mb-4">
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h2 class="card-title font-lg text-primary mb-0">Daftar Jadwal Aktif</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">Hari</th>
                                    <th scope="col" class="font-md">Dokter</th>
                                    <th scope="col" class="font-md">Poliklinik & Ruang</th>
                                    <th scope="col" class="font-md">Waktu Jaga</th>
                                    <th scope="col" class="font-md text-center">Kuota</th>
                                    <th scope="col" class="font-md text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($schedules)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada jadwal dokter yang terdaftar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($schedules as $sched): ?>
                                        <tr>
                                            <td class="font-bold text-primary"><?= $days[$sched['day_of_week']] ?? ucfirst($sched['day_of_week']) ?></td>
                                            <td>
                                                <div class="font-bold">Dr. <?= e($sched['doctor_name']) ?></div>
                                                <small class="text-muted"><?= e($sched['specialization']) ?></small>
                                            </td>
                                            <td>
                                                <div><?= e($sched['polyclinic_name']) ?></div>
                                                <small class="text-muted"><i class="fa fa-door-open"></i> <?= e($sched['room_name'] ?? 'Ruang Belum Diatur') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-info text-info font-md font-bold">
                                                    <?= date('H:i', strtotime($sched['start_time'])) ?> - <?= date('H:i', strtotime($sched['end_time'])) ?> WIB
                                                </span>
                                            </td>
                                            <td class="text-center font-bold font-md"><?= (int)$sched['quota'] ?></td>
                                            <td class="text-center">
                                                <?php if ($sched['is_active']): ?>
                                                    <span class="badge bg-soft-success text-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-soft-danger text-danger">Nonaktif</span>
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

        <!-- Right Column: Add Schedule Form -->
        <div class="col-lg-4 mb-4">
            <div class="card accessible-card border-primary">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title font-lg mb-0" style="color: #fff;">Tambah Jadwal Baru</h2>
                </div>
                <div class="card-body">
                    <form action="<?= url('schedule/store') ?>" method="POST" class="accessible-form">
                        <?= CSRF::getField() ?>

                        <div class="form-group mb-3">
                            <label for="doctor_id" class="form-label font-md font-bold">Dokter Spesialis <span class="text-danger">*</span></label>
                            <select id="doctor_id" name="doctor_id" class="form-control form-control-accessible" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php foreach ($doctors as $doc): ?>
                                    <option value="<?= $doc['id'] ?>">Dr. <?= e($doc['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="polyclinic_id" class="form-label font-md font-bold">Poliklinik <span class="text-danger">*</span></label>
                            <select id="polyclinic_id" name="polyclinic_id" class="form-control form-control-accessible" required>
                                <option value="">-- Pilih Poliklinik --</option>
                                <?php foreach ($polyclinics as $poly): ?>
                                    <option value="<?= $poly['id'] ?>"><?= e($poly['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="room_id" class="form-label font-md font-bold">Ruangan Praktek</label>
                            <select id="room_id" name="room_id" class="form-control form-control-accessible">
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($rooms as $rm): ?>
                                    <option value="<?= $rm['id'] ?>"><?= e($rm['name']) ?> (Lantai <?= e($rm['floor']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="day_of_week" class="form-label font-md font-bold">Hari <span class="text-danger">*</span></label>
                            <select id="day_of_week" name="day_of_week" class="form-control form-control-accessible" required>
                                <option value="">-- Pilih Hari --</option>
                                <?php foreach ($days as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 form-group mb-3">
                                <label for="start_time" class="form-label font-md font-bold">Mulai Jaga <span class="text-danger">*</span></label>
                                <input type="time" id="start_time" name="start_time" class="form-control form-control-accessible" required>
                            </div>
                            <div class="col-6 form-group mb-3">
                                <label for="end_time" class="form-label font-md font-bold">Selesai Jaga <span class="text-danger">*</span></label>
                                <input type="time" id="end_time" name="end_time" class="form-control form-control-accessible" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 form-group mb-3">
                                <label for="quota" class="form-label font-md font-bold">Kuota Pasien <span class="text-danger">*</span></label>
                                <input type="number" id="quota" name="quota" min="1" max="100" value="20" class="form-control form-control-accessible" required>
                            </div>
                            <div class="col-6 form-group mb-3">
                                <label for="booking_available" class="form-label font-md font-bold">Bisa Dipesan?</label>
                                <select id="booking_available" name="booking_available" class="form-control form-control-accessible">
                                    <option value="1">Ya</option>
                                    <option value="0">Tidak</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="effective_from" class="form-label font-md font-bold">Efektif Mulai <span class="text-danger">*</span></label>
                            <input type="date" id="effective_from" name="effective_from" value="<?= date('Y-m-d') ?>" class="form-control form-control-accessible" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="notes" class="form-label font-md font-bold">Catatan Jadwal</label>
                            <textarea id="notes" name="notes" class="form-control form-control-accessible" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-block py-2 font-md font-bold">Simpan Jadwal Praktek</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
