<?php

/**
 * 500 Internal Server Error Page
 */
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .error-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            padding: 20px;
        }

        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
        }

        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .error-title {
            font-size: 32px;
            margin-bottom: 16px;
        }

        .error-message {
            font-size: 18px;
            margin-bottom: 32px;
            opacity: 0.9;
        }

        .error-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: white;
            color: #fa709a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="error-page">
        <div class="error-container">
            <div class="error-code">500</div>
            <h1 class="error-title">Terjadi Kesalahan Server</h1>
            <p class="error-message">
                Maaf, terjadi kesalahan pada server. Tim kami telah diberitahu dan sedang memperbaikinya.
                Silakan coba lagi dalam beberapa saat.
            </p>
            <div class="error-actions">
                <a href="<?= url('dashboard') ?>" class="btn btn-primary">
                    Kembali ke Dashboard
                </a>
                <a href="javascript:location.reload()" class="btn btn-secondary">
                    Refresh Halaman
                </a>
            </div>
        </div>
    </div>
</body>

</html>