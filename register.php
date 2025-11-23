<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $nama = escape($_POST['nama']);
    $email = escape($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nim = escape($_POST['nim']);
    $fakultas = escape($_POST['fakultas']);
    
    if ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } else {
        $check = mysqli_query($conn, "SELECT * FROM pengguna WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO pengguna (nama, email, password, nim, fakultas) 
                      VALUES ('$nama', '$email', '$password_hash', '$nim', '$fakultas')";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat registrasi!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CampussWallet</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 550px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            background: var(--primary-gradient);
            color: white;
            padding: 35px 30px;
            text-align: center;
        }
        
        .register-header .logo {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .register-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: bold;
        }
        
        .register-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .register-body {
            padding: 35px 30px;
        }
        
        .mb-3 {
            margin-bottom: 18px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 14px 15px 14px 45px;
            font-size: 0.95rem;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 4;
            font-size: 1.1rem;
        }
        
        .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #dee2e6;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h2>Buat Akun Baru</h2>
            <p>Mulai kelola keuanganmu sekarang!</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3 position-relative">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control ps-5" id="nama" name="nama" 
                           placeholder="Nama Lengkap" required autofocus>
                </div>
                
                <div class="mb-3 position-relative">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control ps-5" id="email" name="email" 
                           placeholder="Email" required>
                </div>
                
                <div class="mb-3 position-relative">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control ps-5" id="password" name="password" 
                           placeholder="Password" required minlength="6">
                </div>
                
                <div class="mb-3 position-relative">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" class="form-control ps-5" id="confirm_password" name="confirm_password" 
                           placeholder="Konfirmasi Password" required minlength="6">
                </div>
                
                <div class="mb-3 position-relative">
                    <i class="bi bi-card-text input-icon"></i>
                    <input type="text" class="form-control ps-5" id="nim" name="nim" 
                           placeholder="NIM (opsional)">
                </div>
                
                <div class="mb-3 position-relative">
                    <i class="bi bi-building input-icon"></i>
                    <input type="text" class="form-control ps-5" id="fakultas" name="fakultas" 
                           placeholder="Fakultas (opsional)">
                </div>
                
                <button type="submit" name="register" class="btn btn-register">
                    <i class="bi bi-check-circle me-2"></i>
                    Daftar Sekarang
                </button>
            </form>
            
            <div class="login-link">
                Sudah punya akun? 
                <a href="index.php">
                    <i class="bi bi-box-arrow-in-right"></i> Login di sini
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>