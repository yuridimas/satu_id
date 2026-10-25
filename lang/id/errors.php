<?php

declare(strict_types=1);

return [
    'http' => [
        401 => 'Silakan masuk untuk mengakses halaman ini.',
        402 => 'Pembayaran diperlukan untuk mengakses halaman ini. Hubungi administrator aplikasi untuk bantuan.',
        403 => 'Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator aplikasi jika memerlukan akses.',
        404 => 'Halaman ini tidak ditemukan. Periksa alamat atau kembali ke halaman utama.',
        419 => 'Sesi Anda telah kedaluwarsa. Silakan masuk kembali sebelum mengulangi tindakan Anda.',
        429 => 'Anda telah mengirim terlalu banyak permintaan. Tunggu beberapa saat sebelum mencoba lagi.',
        500 => 'Terjadi kesalahan pada sistem kami. Silakan coba lagi nanti atau kembali ke halaman utama.',
        503 => 'Layanan untuk sementara tidak tersedia. Silakan coba lagi nanti.',
    ],

    'generic_action_failed' => 'Terjadi kesalahan pada sistem (kode referensi: :ref). Silakan coba lagi. Jika masalah berlanjut, sertakan kode referensi ini saat menghubungi admin.',
];
