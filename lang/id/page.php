<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sample Page
    |--------------------------------------------------------------------------
    */
    // 'page' => [
    //     'title' => 'Judul Halaman',
    //     'heading' => 'Kepala Halaman',
    //     'subheading' => 'Sub Kepala Halaman',
    //     'navigationLabel' => 'Label Navigasi Halaman',
    //     'section' => [],
    //     'fields' => []
    // ],

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'general_settings' => [
        'title' => 'Pengaturan Umum',
        'heading' => 'Pengaturan Umum',
        'subheading' => 'Kelola pengaturan umum situs di sini.',
        'navigationLabel' => 'Umum',
        'sections' => [
            "site" => [
                "title" => "Situs",
                "description" => "Kelola pengaturan dasar."
            ],
            "theme" => [
                "title" => "Tema",
                "description" => "Ubah tema default."
            ],
        ],
        "fields" => [
            "brand_name" => "Nama Brand",
            "site_active" => "Status Situs",
            "brand_logoHeight" => "Tinggi Logo Brand",
            "brand_logo" => "Logo Brand",
            "site_favicon" => "Favicon Situs",
            "primary" => "Primer",
            "secondary" => "Sekunder",
            "gray" => "Abu-abu",
            "success" => "Berhasil",
            "danger" => "Bahaya",
            "info" => "Info",
            "warning" => "Peringatan",
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Settings
    |--------------------------------------------------------------------------
    */
    'mail_settings' => [
        'title' => 'Pengaturan Email',
        'heading' => 'Pengaturan Email',
        'subheading' => 'Kelola konfigurasi email.',
        'navigationLabel' => 'Email',
        'sections' => [
            "config" => [
                "title" => "Konfigurasi",
                "description" => "deskripsi"
            ],
            "sender" => [
                "title" => "Dari (Pengirim)",
                "description" => "deskripsi"
            ],
            "mail_to" => [
                "title" => "Email kepada",
                "description" => "deskripsi"
            ],
        ],
        "fields" => [
            "placeholder" => [
                "receiver_email" => "Email penerima.."
            ],
            "driver" => "Driver",
            "host" => "Host",
            "port" => "Port",
            "encryption" => "Enkripsi",
            "timeout" => "Timeout",
            "username" => "Nama Pengguna",
            "password" => "Kata Sandi",
            "email" => "Email",
            "name" => "Nama",
            "mail_to" => "Email kepada",
        ],
        "actions" => [
            "send_test_mail" => "Kirim Email Test"
        ]
    ],

];
