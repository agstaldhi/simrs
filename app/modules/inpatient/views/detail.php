<?php
/**
 * Inpatient Patient Detail, EMR & Nursing Notes View
 */
?>

<div class="inpatient-detail-container mt-3">
    
    <!-- Navigation & Back Link -->
    <div class="mb-4">
        <a href="<?= url('inpatient') ?>" style="text-decoration: none; font-weight: bold; color: #1c7ed6; font-size: 14px; display: inline-flex; align-items: center; gap: 4px;">
            ⬅️ Kembali ke Dashboard Ranap
        </a>
    </div>

    <!-- Main Grid Layout -->
    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start;">
        
        <!-- LEFT COLUMN: Patient Card & Bed Info -->
        <div>
            <!-- Patient Banner Card -->
            <div class="card shadow-sm mb-4" style="border-radius: 8px; overflow: hidden; border-top: 5px solid #1c7ed6;">
                <div class="card-body p-4 text-center" style="background-color: #f8f9fa;">
                    <div class="avatar-circle" style="width: 70px; height: 70px; border-radius: 50%; background-color: #1c7ed6; color: #fff; font-size: 28px; font-weight: bold; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                        <?= strtoupper(substr($admission['patient_name'], 0, 1)) ?>
                    </div>
                    <h2 class="font-lg font-bold mb-1"><?= e($admission['patient_name']) ?></h2>
                    <span class="badge" style="background-color: #e7f5ff; color: #1c7ed6; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace;">
                        RM: <?= e($admission['medical_record_number']) ?>
                    </span>
                    <hr style="margin: 16px 0; border: 0; border-top: 1px solid #dee2e6;">
                    <div style="text-align: left; font-size: 14px; color: #495057;">
                        <p style="margin-bottom: 8px;"><strong>NIK:</strong> <?= e($admission['nik'] ?: '-') ?></p>
                        <p style="margin-bottom: 8px;"><strong>Gender:</strong> <?= $admission['gender'] === 'male' ? 'Laki-laki' : 'Perempuan' ?></p>
                        <p style="margin-bottom: 8px;"><strong>Umur:</strong> <?= e($admission['age']) ?> Tahun</p>
                        <p style="margin-bottom: 8px;"><strong>Gol. Darah:</strong> <?= strtoupper(e($admission['blood_type'])) ?></p>
                        <p style="margin-bottom: 0;"><strong>Kontak:</strong> <?= e($admission['phone'] ?: '-') ?></p>
                    </div>
                </div>
            </div>

            <!-- Room / Bed Allocation Card -->
            <div class="card shadow-sm mb-4" style="border-radius: 8px;">
                <div class="card-header p-3" style="background-color: #f1f3f5; border-bottom: 1px solid #dee2e6;">
                    <h3 class="font-md font-bold mb-0">🛏️ Informasi Kamar & Bed</h3>
                </div>
                <div class="card-body p-3" style="font-size: 14px; color: #495057;">
                    <p style="margin-bottom: 8px;"><strong>Ruangan:</strong> <span style="color: #2b8a3e; font-weight: bold;"><?= e($admission['room_name']) ?></span></p>
                    <p style="margin-bottom: 8px;"><strong>Kode Kamar:</strong> <?= e($admission['room_code']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Tipe:</strong> <?= ucfirst(e($admission['room_type'])) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Gedung / Lantai:</strong> <?= e($admission['building']) ?> - Lantai <?= e($admission['floor']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Tgl Masuk:</strong> <?= date('d-m-Y H:i', strtotime($admission['visit_date'])) ?></p>
                    <p style="margin-bottom: 0;"><strong>Dokter DPJP:</strong> <?= e($admission['doctor_name']) ?></p>
                </div>
            </div>

            <!-- Checkout / Discharge Card -->
            <?php if ($admission['visit_status'] !== 'completed'): ?>
                <div class="card shadow-sm mb-4" style="border-radius: 8px; border: 1px solid #ffc9c9; background-color: #fff5f5;">
                    <div class="card-header p-3" style="background-color: #ffe3e3; border-bottom: 1px solid #ffc9c9;">
                        <h3 class="font-md font-bold mb-0" style="color: #c92a2a;">🚪 Pemulangan Pasien (Discharge)</h3>
                    </div>
                    <div class="card-body p-3">
                        <p class="font-sm text-muted mb-3">Gunakan form ini untuk check-out pasien apabila perawatan rawat inap telah selesai.</p>
                        <form method="POST" action="<?= url('inpatient/discharge/' . $admission['id']) ?>" onsubmit="return confirm('Apakah Anda yakin ingin memulangkan pasien ini? Bed akan dikosongkan.');">
                            <?= CSRF::getField() ?>
                            <div class="form-group mb-3">
                                <label for="discharge_notes" class="font-sm font-bold d-block mb-1">Catatan Pemulangan</label>
                                <textarea id="discharge_notes" name="discharge_notes" rows="3" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ffc9c9; font-size: 13px;" placeholder="Instruksi pulang dokter, obat bawaan, atau rencana kontrol..." required></textarea>
                            </div>
                            <button type="submit" class="btn" style="width: 100%; padding: 10px; font-weight: bold; background-color: #fa5252; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                                🚶 Pulangkan Pasien
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: EHR Logs & New Nursing Note -->
        <div>
            <!-- Chief Complaint & Admission Notes -->
            <div class="card shadow-sm mb-4" style="border-radius: 8px;">
                <div class="card-body p-4">
                    <h3 class="font-lg font-bold mb-2">📋 Keluhan Utama Admisi</h3>
                    <p class="font-md" style="line-height: 1.6; color: #212529; background-color: #f8f9fa; padding: 12px; border-radius: 4px; border-left: 4px solid #ced4da;">
                        <?= nl2br(e($admission['chief_complaint'])) ?>
                    </p>
                    <?php if (!empty($admission['notes'])): ?>
                        <h4 class="font-md font-bold mt-3 mb-1">Catatan Tambahan Admisi:</h4>
                        <p class="font-sm text-muted"><?= nl2br(e($admission['notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Nursing Care Form (Catatan Keperawatan SOAP) -->
            <?php if ($admission['visit_status'] !== 'completed'): ?>
                <div class="card shadow-sm mb-4" style="border-radius: 8px;">
                    <div class="card-header p-3" style="background-color: #f1f3f5; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="font-md font-bold mb-0">✍️ Tambah Catatan Asuhan Keperawatan Baru</h3>
                        <span class="badge" style="background-color: #d3f9d8; color: #2b8a3e; padding: 4px 8px; border-radius: 4px; font-weight: bold;">Nurse Role</span>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?= url('inpatient/nursing-note/store') ?>">
                            <?= CSRF::getField() ?>
                            <input type="hidden" name="visit_id" value="<?= e($admission['id']) ?>">
                            <input type="hidden" name="patient_id" value="<?= e($admission['patient_id']) ?>">

                            <!-- Vital Signs Quick Entry -->
                            <div class="vital-signs-entry" style="background-color: #f8f9fa; padding: 16px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e9ecef;">
                                <h4 class="font-sm font-bold mb-3" style="color: #495057;">📊 Tanda Vital Saat Ini (Auto-log Vital Signs)</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                                    <div class="form-group">
                                        <label class="font-xs font-bold mb-1 d-block">TD Sistolik (mmHg)</label>
                                        <input type="number" name="bp_systolic" class="form-control" style="width: 100%; padding: 6px 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="Sistolik">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-xs font-bold mb-1 d-block">TD Diastolik (mmHg)</label>
                                        <input type="number" name="bp_diastolic" class="form-control" style="width: 100%; padding: 6px 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="Diastolik">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-xs font-bold mb-1 d-block">Suhu (°C)</label>
                                        <input type="number" step="0.1" name="temperature" class="form-control" style="width: 100%; padding: 6px 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="36.5">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-xs font-bold mb-1 d-block">Nadi (bpm)</label>
                                        <input type="number" name="pulse" class="form-control" style="width: 100%; padding: 6px 10px; border-radius: 4px; border: 1px solid #ced4da;" placeholder="80">
                                    </div>
                                </div>
                            </div>

                            <!-- SOAP Entries -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Subjective (S)</label>
                                    <textarea name="subjective" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Keluhan subjektif pasien saat visit..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Objective (O)</label>
                                    <textarea name="objective" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Pemeriksaan fisik perawat / kondisi umum..."></textarea>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Assessment (A)</label>
                                    <textarea name="assessment" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Diagnosa keperawatan / evaluasi masalah medis..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Plan (P)</label>
                                    <textarea name="plan" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Rencana asuhan keperawatan / kolaborasi..."></textarea>
                                </div>
                            </div>

                            <!-- Nursing Intervention & Evaluation -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Tindakan Keperawatan (Intervensi)</label>
                                    <textarea name="intervention" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Tindakan yang telah dilakukan perawat (misal: pasang infus, berikan paracetamol)..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="font-sm font-bold mb-1 d-block">Evaluasi Hasil Asuhan</label>
                                    <textarea name="evaluation" rows="3" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ced4da; font-size: 13px;" placeholder="Evaluasi kondisi setelah tindakan (misal: suhu tubuh turun dari 39 ke 37.5)..."></textarea>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: bold; background-color: #2b8a3e; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                                    💾 Simpan Catatan Asuhan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- EMR Timeline Tabs -->
            <div class="card shadow-sm" style="border-radius: 8px; margin-bottom: 24px;">
                <div class="card-body p-4">
                    <h3 class="font-lg font-bold mb-4">🩺 Riwayat Asuhan Keperawatan (Nursing Notes)</h3>

                    <?php if (empty($nursingNotes)): ?>
                        <p class="text-muted font-md text-center py-4">Belum ada catatan asuhan keperawatan untuk periode rawat inap ini.</p>
                    <?php else: ?>
                        <div class="timeline" style="border-left: 2px solid #dee2e6; padding-left: 24px; position: relative;">
                            <?php foreach ($nursingNotes as $note): ?>
                                <div class="timeline-item mb-4" style="position: relative;">
                                    <!-- Bullet -->
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background-color: #2b8a3e; border: 2px solid #fff; position: absolute; left: -31px; top: 4px;"></div>
                                    
                                    <!-- Timestamp & Nurse -->
                                    <div class="font-sm mb-2" style="color: #495057;">
                                        <strong>👩‍⚕️ <?= e($note['nurse_name']) ?></strong> 
                                        <span class="text-muted">• <?= date('d-m-Y H:i', strtotime($note['note_date'])) ?></span>
                                    </div>

                                    <!-- Content Card -->
                                    <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 16px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                                            <div>
                                                <p style="margin-bottom: 4px;"><strong>S:</strong> <?= e($note['subjective'] ?: '-') ?></p>
                                                <p style="margin-bottom: 4px;"><strong>O:</strong> <?= e($note['objective'] ?: '-') ?></p>
                                            </div>
                                            <div>
                                                <p style="margin-bottom: 4px;"><strong>A:</strong> <?= e($note['assessment'] ?: '-') ?></p>
                                                <p style="margin-bottom: 4px;"><strong>P:</strong> <?= e($note['plan'] ?: '-') ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($note['intervention']) || !empty($note['evaluation'])): ?>
                                            <hr style="margin: 8px 0; border: 0; border-top: 1px dashed #ced4da;">
                                            <div style="font-size: 13px;">
                                                <p style="margin-bottom: 4px; color: #2b8a3e;"><strong>Tindakan (Intervensi):</strong> <?= e($note['intervention'] ?: '-') ?></p>
                                                <p style="margin-bottom: 0; color: #1c7ed6;"><strong>Evaluasi:</strong> <?= e($note['evaluation'] ?: '-') ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Doctor SOAP logs (medical_records) -->
            <div class="card shadow-sm" style="border-radius: 8px; margin-bottom: 24px;">
                <div class="card-body p-4">
                    <h3 class="font-lg font-bold mb-4">👨‍⚕️ Riwayat Perkembangan Pasien oleh Dokter (SOAP)</h3>

                    <?php if (empty($medicalRecords)): ?>
                        <p class="text-muted font-md text-center py-4">Belum ada catatan SOAP dari dokter untuk periode rawat inap ini.</p>
                    <?php else: ?>
                        <div class="timeline" style="border-left: 2px solid #dee2e6; padding-left: 24px; position: relative;">
                            <?php foreach ($medicalRecords as $record): ?>
                                <div class="timeline-item mb-4" style="position: relative;">
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background-color: #007bff; border: 2px solid #fff; position: absolute; left: -31px; top: 4px;"></div>
                                    
                                    <div class="font-sm mb-2" style="color: #495057;">
                                        <strong>👨‍⚕️ <?= e($record['doctor_name']) ?></strong> 
                                        <span class="text-muted">• <?= date('d-m-Y H:i', strtotime($record['created_at'])) ?></span>
                                    </div>

                                    <div style="background-color: #f1f3f5; border: 1px solid #dee2e6; border-radius: 6px; padding: 16px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                                            <div>
                                                <p style="margin-bottom: 4px;"><strong>Subjective (S):</strong> <?= e($record['subjective'] ?: '-') ?></p>
                                                <p style="margin-bottom: 4px;"><strong>Objective (O):</strong> <?= e($record['objective'] ?: '-') ?></p>
                                            </div>
                                            <div>
                                                <p style="margin-bottom: 4px;"><strong>Assessment (A):</strong> <?= e($record['assessment'] ?: '-') ?></p>
                                                <p style="margin-bottom: 0;"><strong>Plan (P):</strong> <?= e($record['plan'] ?: '-') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vital Signs logs -->
            <div class="card shadow-sm" style="border-radius: 8px;">
                <div class="card-body p-4">
                    <h3 class="font-lg font-bold mb-4">📈 Monitoring Tanda-Tanda Vital</h3>

                    <?php if (empty($vitalSigns)): ?>
                        <p class="text-muted font-md text-center py-4">Belum ada pencatatan tanda-tanda vital untuk periode rawat inap ini.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                                <thead>
                                    <tr style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                        <th style="padding: 10px; font-weight: bold;">Tanggal/Jam</th>
                                        <th style="padding: 10px; font-weight: bold;">TD (mmHg)</th>
                                        <th style="padding: 10px; font-weight: bold;">Nadi (bpm)</th>
                                        <th style="padding: 10px; font-weight: bold;">Suhu (°C)</th>
                                        <th style="padding: 10px; font-weight: bold;">Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vitalSigns as $vs): ?>
                                        <tr style="border-bottom: 1px solid #e9ecef;">
                                            <td style="padding: 10px;"><?= date('d-m-Y H:i', strtotime($vs['measured_at'])) ?></td>
                                            <td style="padding: 10px;">
                                                <?= ($vs['blood_pressure_systolic'] && $vs['blood_pressure_diastolic']) ? ($vs['blood_pressure_systolic'] . '/' . $vs['blood_pressure_diastolic']) : '-' ?>
                                            </td>
                                            <td style="padding: 10px;"><?= e($vs['heart_rate'] ?: '-') ?></td>
                                            <td style="padding: 10px;">
                                                <?php if ($vs['temperature']): ?>
                                                    <span style="<?= $vs['temperature'] > 37.8 ? 'color: #fa5252; font-weight: bold;' : '' ?>">
                                                        <?= e($vs['temperature']) ?> °C
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 10px;"><?= e($vs['nurse_name'] ?: 'System') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
