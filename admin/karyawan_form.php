<?php

/**
 * Partial form tambah/edit karyawan.
 *
 * Variabel:
 * - $formAction string
 * - $form array
 * - $errors array
 * - $isEdit bool
 * - $current array|null
 * - $sendWa bool
 */
$isEdit = $isEdit ?? false;
$current = $current ?? null;
$sendWa = $sendWa ?? true;
?>
<div class="grid grid--2-1">
    <form method="post" action="<?= e($formAction) ?>" class="card" data-single-submit novalidate>
        <?= Csrf::field() ?>

        <div class="card__head">
            <div>
                <h2 class="card__title"><?= $isEdit ? 'Informasi Karyawan' : 'Data Karyawan Baru' ?></h2>
                <p class="card__sub"><?= $isEdit ? 'Perbarui data di bawah. Notifikasi WhatsApp dikirim setelah perubahan tersimpan.' : 'Semua field bertanda bintang wajib diisi.' ?></p>
            </div>
            <span class="badge badge--info badge--plain"><?= icon($isEdit ? 'edit' : 'user') ?> <?= $isEdit ? 'Mode edit' : 'Karyawan baru' ?></span>
        </div>

        <div class="card__body">
            <?php if (isset($errors['umum'])): ?>
                <div class="alert alert--error" role="alert">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg></span>
                    <span class="alert__text"><?= e($errors['umum']) ?></span>
                </div>
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label for="nik">NIK <span class="req">*</span></label>
                    <input type="text" id="nik" name="nik" class="input <?= isset($errors['nik']) ? 'is-invalid' : '' ?>"
                           value="<?= e($form['nik']) ?>" inputmode="numeric" maxlength="20" required>
                    <?php if (isset($errors['nik'])): ?><span class="field__error"><?= e($errors['nik']) ?></span><?php else: ?><span class="field__hint">Hanya angka, 6&ndash;20 digit, tidak boleh sama dengan karyawan lain.</span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="nama">Nama lengkap <span class="req">*</span></label>
                    <input type="text" id="nama" name="nama" class="input <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                           value="<?= e($form['nama']) ?>" maxlength="100" required>
                    <?php if (isset($errors['nama'])): ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="jabatan">Jabatan <span class="req">*</span></label>
                    <input type="text" id="jabatan" name="jabatan" class="input <?= isset($errors['jabatan']) ? 'is-invalid' : '' ?>"
                           value="<?= e($form['jabatan']) ?>" maxlength="100" required>
                    <?php if (isset($errors['jabatan'])): ?><span class="field__error"><?= e($errors['jabatan']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="no_wa">Nomor WhatsApp <span class="req">*</span></label>
                    <input type="tel" id="no_wa" name="no_wa" class="input <?= isset($errors['no_wa']) ? 'is-invalid' : '' ?>"
                           value="<?= e($form['no_wa']) ?>" placeholder="08xxxxxxxxxx" required>
                    <?php if (isset($errors['no_wa'])): ?><span class="field__error"><?= e($errors['no_wa']) ?></span><?php else: ?><span class="field__hint">Notifikasi perubahan dan absensi dikirim ke nomor ini.</span><?php endif; ?>
                </div>

                <div class="field field--full">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= e($form['email']) ?>" maxlength="120" required>
                    <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="password">Password <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               placeholder="<?= $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' ?>"
                               autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>
                               data-password-strength="#adminPasswordStrength">
                        <button type="button" class="input-group__button" data-toggle-password="password" aria-label="Tampilkan password">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                    <div class="strength" id="adminPasswordStrength">
                        <div class="strength__bars"><span></span><span></span><span></span><span></span></div>
                        <span class="strength__label">Kekuatan</span>
                    </div>
                    <?php if (isset($errors['password'])): ?><span class="field__error"><?= e($errors['password']) ?></span><?php else: ?><span class="field__hint"><?= $isEdit ? 'Biarkan kosong untuk mempertahankan password saat ini.' : 'Gunakan huruf dan angka, minimal 8 karakter.' ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="password_confirm">Konfirmasi password <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>
                    <div class="input-group">
                        <input type="password" id="password_confirm" name="password_confirm" class="input <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                               placeholder="<?= $isEdit ? 'Ulangi password baru' : 'Ulangi password' ?>" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                        <button type="button" class="input-group__button" data-toggle-password="password_confirm" aria-label="Tampilkan konfirmasi password">
                            <?= icon('eye') ?>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?><span class="field__error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
                </div>
            </div>

            <?php if (!$isEdit): ?>
                <label class="check-row mt-3">
                    <input type="checkbox" name="send_wa" value="1" <?= $sendWa ? 'checked' : '' ?>>
                    <span>
                        <strong>Kirim notifikasi WhatsApp pendaftaran</strong>
                        <small>Karyawan baru menerima pesan berisi nama, NIK, dan jabatan.</small>
                    </span>
                </label>
            <?php else: ?>
                <div class="alert alert--info mt-3" style="margin-bottom:0">
                    <span class="alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg></span>
                    <span class="alert__text">Setelah data berhasil diperbarui, sistem otomatis mengirim WhatsApp berisi data terbaru kepada karyawan.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="card__footer form-actions">
            <a href="<?= url('admin/karyawan.php') ?>" class="btn btn--secondary">Batal</a>
            <button type="submit" class="btn btn--primary">
                <?= icon('check') ?> <?= $isEdit ? 'Simpan Perubahan &amp; Kirim WA' : 'Simpan Karyawan' ?>
            </button>
        </div>
    </form>

    <aside class="grid" style="align-content:start">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title"><?= $isEdit ? 'Ringkasan Akun' : 'Alur Setelah Disimpan' ?></h2>
                    <p class="card__sub"><?= $isEdit ? 'Informasi akun yang sedang diedit.' : 'Proses otomatis yang dijalankan sistem.' ?></p>
                </div>
            </div>
            <div class="card__body">
                <?php if ($isEdit && is_array($current)): ?>
                    <div class="person mb-2">
                        <span class="avatar avatar--lg <?= avatar_tone($current['id']) ?>"><?= e(initials((string) $current['nama'])) ?></span>
                        <span class="person__text">
                            <span class="person__name" style="max-width:none"><?= e($current['nama']) ?></span>
                            <span class="person__meta"><?= e($current['jabatan']) ?></span>
                        </span>
                    </div>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('id-card') ?></span>
                            <span><span class="info-row__label">NIK</span><span class="info-row__value"><?= e($current['nik']) ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('phone') ?></span>
                            <span><span class="info-row__label">WhatsApp</span><span class="info-row__value"><?= e(phone_display((string) $current['no_wa'])) ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('calendar') ?></span>
                            <span><span class="info-row__label">Terdaftar</span><span class="info-row__value"><?= e(tanggal_id(date('Y-m-d', strtotime((string) $current['created_at'])))) ?></span></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('user') ?></span>
                            <span><span class="info-row__label">Langkah 1</span><span class="info-row__value">Data karyawan disimpan ke database.</span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('whatsapp') ?></span>
                            <span><span class="info-row__label">Langkah 2</span><span class="info-row__value">WhatsApp pendaftaran dikirim ke karyawan.</span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row__icon"><?= icon('shield') ?></span>
                            <span><span class="info-row__label">Langkah 3</span><span class="info-row__value">Password disimpan sebagai hash yang aman.</span></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__body">
                <div class="flex-between mb-2">
                    <strong class="text-sm">Status WhatsApp</strong>
                    <span class="badge badge--<?= wa_mode_active() ? 'success' : 'info' ?>"><?= wa_mode_active() ? 'Aktif' : 'Simulasi' ?></span>
                </div>
                <p class="text-sm text-muted" style="line-height:1.6">
                    <?= wa_mode_active()
                        ? 'Fonnte aktif. Pesan akan dikirim melalui API WhatsApp dan dicatat pada menu WhatsApp.'
                        : 'Token Fonnte belum diaktifkan. Pesan hanya dicatat sebagai simulasi dan tidak benar-benar dikirim.' ?>
                </p>
                <a href="<?= url('admin/wa.php') ?>" class="btn btn--secondary btn--sm btn--block mt-2"><?= icon('whatsapp') ?> Buka WhatsApp Center</a>
            </div>
        </section>
    </aside>
</div>
