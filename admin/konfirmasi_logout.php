<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Logout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- BOOTSTRAP ICONS (OFFLINE) -->
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        /* Tema Warna (Sama dengan User) */
        :root {
            --bs-primary: #0d6efd; /* Biru Tua */
        --bg-soft: #f3f4f6;    /* Background lembut */
        --bg-card: #ffffff;    /* Card Putih */
        --text-dark: #212529;
            --text-muted: #6c757d;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-soft);
        }

        /* Modal Overlay Fullscreen */
        .modal-fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Background overlay transparan ringan */
            background-color: rgba(33, 37, 41, 0.4); 
            backdrop-filter: blur(5px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        /* Animasi masuk */
        .modal-fullscreen-overlay.show {
            opacity: 1;
        }

        /* Card Konfirmasi (Bersih & Rapi) */
        .confirm-card {
            background: var(--bg-card);
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Bayangan lembut */
            padding: 2.5rem;
            transform: scale(0.9);
            transition: transform 0.3s ease-in-out;
            border: 1px solid #e5e7eb;
        }

        .modal-fullscreen-overlay.show .confirm-card {
            transform: scale(1);
        }
    </style>
</head>

<body>

    <!-- MODAL FULLSCREEN -->
    <div class="modal-fullscreen-overlay" id="confirmModal">
        
        <div class="confirm-card text-center">
            <!-- Icon Peringatan (Gunakan Icon User/Logout Netral) -->
            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-4 mx-auto" style="width: 70px; height: 70px;">
                <i class="bi bi-person-x fs-2"></i>
            </div>

            <h3 class="fw-bold text-dark mb-3">Keluar Akun?</h3>
            <p class="text-muted mb-5 mx-auto" style="max-width: 320px; line-height: 1.6;">
                Apakah Anda yakin ingin mengakhiri sesi ini? Anda perlu login ulang untuk mengakses sistem.
            </p>

            <div class="d-grid gap-3">
                <!-- Tombol Batal -->
                <button onclick="history.back()" class="btn btn-outline-secondary py-2.5 fw-bold">
                    <i class="bi bi-x-circle me-2"></i> Batal
                </button>
                
                <!-- Tombol Logout -->
                <a href="../auth/logout.php" class="btn btn-primary py-2.5 fw-bold text-decoration-none">
                    <i class="bi bi-check-circle me-2"></i> Ya, Logout
                </a>
            </div>
        </div>

    </div>

    <!-- BOOTSTRAP 5 JS (OFFLINE) -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>

    <script>
        // Animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('confirmModal').classList.add('show');
            }, 50); // Delay sangat kecil agar transisi mulus
        });
    </script>

</body>
</html>