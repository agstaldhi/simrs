<?php
/**
 * View Cashier Payment Terminal
 */
$invoice = $data['invoice'] ?? null;
$items = $data['items'] ?? [];
$payments = $data['payments'] ?? [];
?>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title-box">
            <h1 class="page-title font-xl">Kasir Terminal Pembayaran</h1>
            <p class="page-subtitle text-muted font-md">Proses transaksi pembayaran tagihan pasien, rincian biaya penunjang medis, dan cetak kuitansi.</p>
        </div>
        <div class="page-action">
            <a href="<?= url('billing/invoices') ?>" class="btn btn-outline-secondary font-bold font-md">
                <i class="fa fa-arrow-left"></i> Kembali ke Daftar Tagihan
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Invoice Details & Items list -->
        <div class="col-lg-8 mb-4">
            <!-- Patient / Invoice Summary Card -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light">
                    <h3 class="card-title font-lg text-primary mb-0">Rincian Pasien & Invoice</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <span class="text-muted font-sm d-block">Nomor Invoice</span>
                            <strong class="font-lg text-primary"><?= e($invoice['invoice_number']) ?></strong>
                            <small class="text-muted d-block">Tanggal: <?= date('d/m/Y H:i', strtotime($invoice['invoice_date'])) ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <span class="text-muted font-sm d-block">Pasien (RM)</span>
                            <strong class="font-md"><?= e($invoice['patient_name']) ?></strong>
                            <span class="badge bg-soft-secondary text-secondary ml-1"><?= e($invoice['medical_record_number']) ?></span>
                            <small class="text-muted d-block">No. Visit: <?= e($invoice['visit_number'] ?: '-') ?></small>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted font-sm d-block">Metode Penjaminan</span>
                            <span class="badge bg-soft-primary text-primary font-bold"><?= strtoupper($invoice['invoice_type']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted font-sm d-block">Status Pembayaran</span>
                            <?php if ($invoice['payment_status'] === 'paid'): ?>
                                <span class="badge bg-soft-success text-success font-bold"><i class="fa fa-check-circle"></i> Lunas</span>
                            <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                                <span class="badge bg-soft-warning text-warning font-bold">Dibayar Sebagian</span>
                            <?php else: ?>
                                <span class="badge bg-soft-danger text-danger font-bold">Belum Dibayar</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items Table -->
            <div class="card accessible-card mb-4">
                <div class="card-header bg-light">
                    <h3 class="card-title font-lg text-primary mb-0">Item Rincian Biaya</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">Layanan / Item</th>
                                    <th scope="col" class="font-md">Kategori</th>
                                    <th scope="col" class="font-md text-center">Jumlah</th>
                                    <th scope="col" class="font-md text-right">Harga Satuan</th>
                                    <th scope="col" class="font-md text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">Tidak ada rincian item biaya untuk tagihan ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="font-bold"><?= e($item['item_name']) ?></div>
                                                <small class="text-muted"><?= e($item['item_code']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-info text-info font-sm"><?= e(ucfirst($item['item_type'])) ?></span>
                                            </td>
                                            <td class="text-center font-bold"><?= e($item['quantity']) ?></td>
                                            <td class="text-right"><?= formatRupiah($item['unit_price']) ?></td>
                                            <td class="text-right font-bold"><?= formatRupiah($item['subtotal']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Past Payments -->
            <div class="card accessible-card">
                <div class="card-header bg-light">
                    <h3 class="card-title font-lg text-primary mb-0">Riwayat Pembayaran Kuitansi</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="font-md">No. Kuitansi / Tanggal</th>
                                    <th scope="col" class="font-md">Metode Bayar</th>
                                    <th scope="col" class="font-md">Penerima Kasir</th>
                                    <th scope="col" class="font-md text-right">Jumlah Dibayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">Belum ada transaksi pembayaran terdaftar untuk invoice ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td class="font-bold text-success"><?= e($pay['payment_number']) ?>
                                                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($pay['payment_date'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-success text-success font-bold"><?= strtoupper($pay['payment_method']) ?></span>
                                            </td>
                                            <td class="font-bold"><?= e($pay['cashier_name']) ?></td>
                                            <td class="text-right font-bold text-success"><?= formatRupiah($pay['amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Processor Panel -->
        <div class="col-lg-4">
            <div class="card accessible-card position-sticky" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title font-lg mb-0 text-white">Proses Pembayaran</h3>
                </div>
                <div class="card-body">
                    <!-- Pricing totals summary -->
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal Tagihan:</span>
                        <span class="font-bold"><?= formatRupiah($invoice['subtotal']) ?></span>
                    </div>
                    <?php if ($invoice['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Diskon (<?= $invoice['discount_percentage'] ?>%):</span>
                            <span>- <?= formatRupiah($invoice['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($invoice['tax_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pajak (<?= $invoice['tax_percentage'] ?>%):</span>
                            <span>+ <?= formatRupiah($invoice['tax_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="font-bold text-dark">Total Biaya:</span>
                        <span class="font-lg font-bold text-primary"><?= formatRupiah($invoice['total_amount']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Sudah Dibayar:</span>
                        <span class="font-bold text-success"><?= formatRupiah($invoice['paid_amount']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4 bg-soft-danger p-2 rounded">
                        <span class="font-bold text-danger">Sisa Tagihan (Piutang):</span>
                        <strong class="font-lg text-danger"><?= formatRupiah($invoice['outstanding_amount']) ?></strong>
                    </div>

                    <?php if ($invoice['outstanding_amount'] > 0): ?>
                        <!-- Form Kasir Pembayaran -->
                        <form action="<?= url('billing/payments/store') ?>" method="POST">
                            <?= CSRF::getField() ?>
                            <input type="hidden" name="invoice_id" value="<?= e($invoice['id']) ?>">

                            <div class="mb-3">
                                <label for="payment_method" class="form-label font-bold">Metode Pembayaran</label>
                                <select id="payment_method" name="payment_method" class="form-control" required onchange="toggleCardInputs(this.value)">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="debit_card">Kartu Debit</option>
                                    <option value="credit_card">Kartu Kredit</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                    <option value="bpjs">BPJS Kesehatan</option>
                                    <option value="insurance">Asuransi Swasta</option>
                                </select>
                            </div>

                            <!-- Card/Transfer inputs toggled dynamically -->
                            <div id="card_inputs" class="border p-2 rounded mb-3 bg-light" style="display:none;">
                                <div class="mb-2">
                                    <label for="bank_name" class="form-label font-sm font-bold">Nama Bank Penerbit</label>
                                    <input type="text" id="bank_name" name="bank_name" class="form-control form-control-sm" placeholder="Contoh: BCA / Mandiri">
                                </div>
                                <div class="mb-2">
                                    <label for="card_number" class="form-label font-sm font-bold">Nomor Kartu / Rekening</label>
                                    <input type="text" id="card_number" name="card_number" class="form-control form-control-sm" placeholder="Digit terakhir atau No Rek">
                                </div>
                                <div class="mb-2">
                                    <label for="transaction_reference" class="form-label font-sm font-bold">No. Ref Transaksi (EDC/Transfer)</label>
                                    <input type="text" id="transaction_reference" name="transaction_reference" class="form-control form-control-sm" placeholder="Ref/Approval code">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label font-bold">Jumlah Bayar (Rp)</label>
                                <input 
                                    type="number" 
                                    id="amount" 
                                    name="amount" 
                                    class="form-control form-control-accessible font-bold text-success font-lg" 
                                    value="<?= intval($invoice['outstanding_amount']) ?>"
                                    max="<?= intval($invoice['outstanding_amount']) ?>"
                                    min="100" 
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label font-bold">Catatan Pembayaran</label>
                                <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Catatan kuitansi pembayaran kasir..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-block font-bold py-3 font-lg">
                                <i class="fa fa-cash-register"></i> Simpan Transaksi Pembayaran
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success text-center mb-0 font-bold">
                            <i class="fa fa-check-circle mr-1"></i> TAGIHAN SUDAH LUNAS
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCardInputs(method) {
    var cardInputs = document.getElementById('card_inputs');
    if (method === 'debit_card' || method === 'credit_card' || method === 'bank_transfer') {
        cardInputs.style.display = 'block';
    } else {
        cardInputs.style.display = 'none';
    }
}
</script>
