<!-- views/login.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Worklog - Login</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/adminlte.min.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(30, 58, 138, 0.1) 0px, transparent 50%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            padding: 40px;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 8px;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .brand-logo i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-card p.subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
            text-align: center;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            margin-bottom: 8px;
        }

        .input-group-modern {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-modern i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color 0.3s;
        }

        .input-group-modern .form-control {
            padding: 12px 16px 12px 48px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            font-size: 15px;
            height: auto;
            transition: all 0.3s;
        }

        .input-group-modern .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .input-group-modern .form-control:focus + i {
            color: #3b82f6;
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 15px;
            color: white;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
            transition: all 0.3s;
        }

        .btn-login:hover {
            opacity: 0.9;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
            transform: scale(1.01);
        }

        .btn-login:disabled {
            background: #94a3b8;
            box-shadow: none;
        }

        /* Decorative circles */
        .circle {
            position: absolute;
            border-radius: 50%;
            background: var(--primary-gradient);
            filter: blur(80px);
            opacity: 0.15;
            z-index: 1;
        }

        .circle-1 { width: 300px; height: 300px; top: -100px; left: -100px; }
        .circle-2 { width: 400px; height: 400px; bottom: -150px; right: -100px; }

    </style>
</head>
<body>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <i class="bi bi-journal-check"></i>
                <span>WorkLOG</span>
            </div>
            <p class="subtitle">Monitoring Produktivitas Pegawai</p>

            <form id="loginForm">
                <div class="form-group mb-3">
                    <label class="form-label">Nomor Pegawai</label>
                    <div class="input-group-modern">
                        <input type="text" name="npp" class="form-control" placeholder="Masukkan NPP Anda" required autocomplete="username">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label">Kata Sandi</label>
                    <div class="input-group-modern">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

                <button type="submit" id="btnLogin" class="btn-login">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnLogin');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Autentikasi...';

        fetch('api/auth.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(async r => {
            const res = await r.json();
            if(!r.ok) throw new Error(res.message || 'Login Gagal');
            return res;
        })
        .then(res => {
            Swal.fire({ 
                icon: 'success', 
                title: 'Berhasil', 
                text: 'Selamat datang kembali!', 
                timer: 1000, 
                showConfirmButton: false,
                background: '#fff',
                color: '#1e3a8a'
            }).then(() => { 
                window.location.href = 'index.php'; 
            });
        })
        .catch(err => {
            Swal.fire({ 
                icon: 'error', 
                title: 'Login Gagal', 
                text: err.message,
                confirmButtonColor: '#3b82f6'
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
    </script>
</body>
</html>
