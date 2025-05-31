<?php

return [
    'profile' => [
        'personal_info' => [
            'heading' => 'Informasi Pribadi',
            'subheading' => 'Kelola informasi profil pribadi Anda.',
            'submit' => [
                'label' => 'Perbarui',
            ],
        ],
        'password' => [
            'heading' => 'Ubah Kata Sandi',
            'subheading' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.',
            'submit' => [
                'label' => 'Perbarui Kata Sandi',
            ],
        ],
        '2fa' => [
            'title' => 'Autentikasi Dua Faktor',
            'description' => 'Tingkatkan keamanan akun Anda dengan mengaktifkan autentikasi dua faktor.',
            'must_enable' => 'Anda harus mengaktifkan autentikasi dua faktor untuk menggunakan aplikasi ini.',
            'not_enabled' => [
                'title' => 'Autentikasi Dua Faktor Belum Diaktifkan',
                'description' => 'Autentikasi dua faktor menambahkan lapisan keamanan tambahan pada akun Anda.',
            ],
            'enabled' => [
                'title' => 'Autentikasi Dua Faktor Diaktifkan',
                'description' => 'Autentikasi dua faktor telah diaktifkan untuk akun Anda.',
                'store_codes' => 'Simpan kode pemulihan ini di tempat yang aman. Kode ini dapat digunakan untuk memulihkan akses jika perangkat autentikasi dua faktor Anda hilang.',
            ],
            'finish_enabling' => [
                'title' => 'Selesaikan Pengaktifan Autentikasi Dua Faktor',
                'description' => 'Untuk menyelesaikan pengaktifan autentikasi dua faktor, pindai kode QR berikut dengan aplikasi autentikator ponsel Anda atau masukkan kunci pengaturan dan berikan kode OTP yang dihasilkan.',
            ],
            'setup_key' => 'Kunci Pengaturan:',
        ],
        'sanctum' => [
            'title' => 'Token API',
            'description' => 'Kelola token API untuk mengakses aplikasi.',
            'create' => [
                'message' => 'Buat token API baru untuk mengakses aplikasi secara terprogram.',
            ],
            'copied' => [
                'label' => 'Disalin!',
            ],
        ],
    ],
    'fields' => [
        'avatar' => 'Avatar',
        'email' => 'Email',
        'name' => 'Nama',
        'password' => 'Kata Sandi',
        'password_confirmation' => 'Konfirmasi Kata Sandi',
        'current_password' => 'Kata Sandi Saat Ini',
        'new_password' => 'Kata Sandi Baru',
        'new_password_confirmation' => 'Konfirmasi Kata Sandi Baru',
        'code' => 'Kode',
        'recovery_code' => 'Kode Pemulihan',
    ],
    'actions' => [
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'enable' => 'Aktifkan',
        'disable' => 'Nonaktifkan',
        'remove' => 'Hapus',
        'create' => 'Buat',
        'copy' => 'Salin',
        'confirm' => 'Konfirmasi',
        'regenerate_codes' => 'Regenerasi Kode Pemulihan',
        'show_codes' => 'Tampilkan Kode Pemulihan',
        'hide_codes' => 'Sembunyikan Kode Pemulihan',
    ],
    'notifications' => [
        'profile_updated' => [
            'title' => 'Profil Diperbarui',
            'body' => 'Informasi profil Anda telah berhasil diperbarui.',
        ],
        'password_updated' => [
            'title' => 'Kata Sandi Diperbarui',
            'body' => 'Kata sandi Anda telah berhasil diperbarui.',
        ],
        '2fa_enabled' => [
            'title' => 'Autentikasi Dua Faktor Diaktifkan',
            'body' => 'Autentikasi dua faktor telah berhasil diaktifkan.',
        ],
        '2fa_disabled' => [
            'title' => 'Autentikasi Dua Faktor Dinonaktifkan',
            'body' => 'Autentikasi dua faktor telah berhasil dinonaktifkan.',
        ],
        '2fa_confirmed' => [
            'title' => 'Autentikasi Dua Faktor Dikonfirmasi',
            'body' => 'Autentikasi dua faktor telah berhasil dikonfirmasi.',
        ],
        'codes_regenerated' => [
            'title' => 'Kode Pemulihan Diregenrasi',
            'body' => 'Kode pemulihan baru telah berhasil dibuat.',
        ],
        'token_created' => [
            'title' => 'Token Dibuat',
            'body' => 'Token API baru telah berhasil dibuat.',
        ],
        'token_deleted' => [
            'title' => 'Token Dihapus',
            'body' => 'Token API telah berhasil dihapus.',
        ],
    ],
];
