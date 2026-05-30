<?php
/**
 * Bed Status / Bed Management View
 */
?>

<div class="beds-container mt-3">
    
    <!-- Page Header -->
    <div class="page-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <a href="<?= url('inpatient') ?>" style="text-decoration: none; font-weight: bold; color: #1c7ed6; font-size: 14px; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
                ⬅️ Kembali ke Dashboard Ranap
            </a>
            <h1 class="font-xl font-bold">🛏️ Status Tempat Tidur (Bed Management)</h1>
            <p class="text-muted font-md">Monitor real-time ketersediaan bed, status hunian kamar inap, dan sebaran kelas perawatan.</p>
        </div>
    </div>

    <!-- Occupancy Statistics Summary -->
    <div class="card shadow-sm mb-4" style="border-radius: 8px; background-color: #f8f9fa;">
        <div class="card-body p-4">
            <h3 class="font-md font-bold mb-3">📊 Ringkasan Kapasitas Ruang Inap</h3>
            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <!-- BOR Progress Bar -->
                <div style="flex: 1; min-width: 250px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px;">
                        <strong>Bed Occupancy Rate (BOR):</strong>
                        <strong style="color: #0ca678;"><?= e($stats['bor']) ?>%</strong>
                    </div>
                    <div style="width: 100%; height: 16px; background-color: #dee2e6; border-radius: 8px; overflow: hidden; display: flex;">
                        <div style="width: <?= e($stats['bor']) ?>%; height: 100%; background-color: #0ca678;"></div>
                    </div>
                </div>
                
                <!-- Quick counters -->
                <div style="display: flex; gap: 16px; font-size: 14px; color: #495057;">
                    <div>🟢 <strong>Tersedia:</strong> <?= e($stats['available_beds']) ?> Bed</div>
                    <div>🔴 <strong>Terisi:</strong> <?= e($stats['occupied_beds']) ?> Bed</div>
                    <div>🏢 <strong>Total:</strong> <?= e($stats['total_beds']) ?> Bed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bed Grid Visualizer -->
    <div class="bed-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($beds as $room): ?>
            <?php 
                $occupancyRate = $room['capacity'] > 0 ? round(($room['occupied_beds'] / $room['capacity']) * 100) : 0;
                
                // Determine colors based on occupancy
                $cardBorder = '#dee2e6';
                $badgeBg = '#e7f5ff';
                $badgeText = '#1c7ed6';
                
                if ($room['available_beds'] == 0) {
                    $cardBorder = '#ffc9c9';
                    $badgeBg = '#fff5f5';
                    $badgeText = '#fa5252';
                } elseif ($room['occupied_beds'] > 0) {
                    $cardBorder = '#ffe3e3';
                    $badgeBg = '#fff4e6';
                    $badgeText = '#f76707';
                } else {
                    $cardBorder = '#d3f9d8';
                    $badgeBg = '#ebfbee';
                    $badgeText = '#2b8a3e';
                }
            ?>
            <div class="card shadow-sm" style="border-radius: 8px; border: 1px solid <?= $cardBorder ?>; overflow: hidden; display: flex; flex-direction: column;">
                
                <!-- Room Header -->
                <div class="card-header p-3" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong class="font-md" style="color: #212529;"><?= e($room['name']) ?></strong>
                        <span class="text-muted font-xs d-block" style="font-family: monospace;">Kode: <?= e($room['code']) ?></span>
                    </div>
                    <span class="badge" style="background-color: <?= $badgeBg ?>; color: <?= $badgeText ?>; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">
                        <?= $room['available_beds'] == 0 ? '🔴 Penuh' : ($room['occupied_beds'] > 0 ? '🟡 Terisi' : '🟢 Kosong') ?>
                    </span>
                </div>

                <!-- Room Body -->
                <div class="card-body p-3" style="font-size: 13px; color: #495057; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <p style="margin-bottom: 6px;"><strong>Departemen:</strong> <?= e($room['department_name']) ?></p>
                        <p style="margin-bottom: 6px;"><strong>Lokasi:</strong> Gedung <?= e($room['building']) ?>, Lantai <?= e($room['floor']) ?></p>
                        <p style="margin-bottom: 10px;"><strong>Fasilitas:</strong> <span class="text-muted font-sm"><?= e($room['facilities'] ?: '-') ?></span></p>
                    </div>

                    <!-- Progress Bar Bed Usage -->
                    <div style="border-top: 1px dashed #dee2e6; padding-top: 10px; margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px;">
                            <span>Kapasitas Bed: <strong><?= e($room['occupied_beds']) ?>/<?= e($room['capacity']) ?></strong></span>
                            <strong><?= e($occupancyRate) ?>% Terisi</strong>
                        </div>
                        <div style="width: 100%; height: 8px; background-color: #e9ecef; border-radius: 4px; overflow: hidden; display: flex;">
                            <div style="width: <?= e($occupancyRate) ?>%; height: 100%; background-color: <?= $room['available_beds'] == 0 ? '#fa5252' : ($room['occupied_beds'] > 0 ? '#f76707' : '#2b8a3e') ?>;"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
