<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cpf_no = trim($_POST['cpf_no']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($cpf_no) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if CPF_NO exists
        $check_sql = "SELECT id FROM users WHERE cpf_no = :cpf_no OR email = :email";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ':cpf_no', $cpf_no);
        oci_bind_by_name($check_stid, ':email', $email);
        oci_execute($check_stid);
        
        if (oci_fetch_assoc($check_stid)) {
            $error = "CPF_NO or email already exists";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (cpf_no, email, password) VALUES (:cpf_no, :email, :password)";
            $insert_stid = oci_parse($conn, $insert_sql);
            oci_bind_by_name($insert_stid, ':cpf_no', $cpf_no);
            oci_bind_by_name($insert_stid, ':email', $email);
            oci_bind_by_name($insert_stid, ':password', $hashed_password);
            
            if (oci_execute($insert_stid)) {
                $success = "Account created successfully! Please sign in.";
            } else {
                $e = oci_error($insert_stid);
                $error = "Error creating account: " . $e['message'];
            }
            
            oci_free_statement($insert_stid);
        }
        
        oci_free_statement($check_stid);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: 
                radial-gradient(ellipse at 20% 80%, #0f172a 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, #1e1b4b 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, #312e81 0%, #0f172a 100%);
            background-color: #0f172a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
            animation: meshMove 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes meshMove {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(2%, -2%) scale(1.02); }
            66% { transform: translate(-1%, 1%) scale(0.98); }
        }
        
        .signup-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 50px;
            border-radius: 16px;
            width: 100%;
            max-width: 420px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
            opacity: 0;
            transform: translateY(20px);
            animation: cardSlideIn 0.6s ease-out forwards;
        }
        
        @keyframes cardSlideIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .signup-container h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        
        .form-group {
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInUp 0.4s ease-out forwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.15s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.25s; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transition: all 0.3s ease;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .success-message {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        @keyframes fadeIn { to { opacity: 1; } }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: buttonPopIn 0.4s ease-out 0.3s forwards;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .btn-submit:hover::before { opacity: 0.2; }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.2);
        }
        
        .btn-submit:active { transform: translateY(0) scale(0.98); }
        
        .btn-submit span { position: relative; z-index: 1; }
        
        @keyframes buttonPopIn {
            0% { opacity: 0; transform: scale(0.9) translateY(10px); }
            70% { transform: scale(1.02) translateY(-2px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            opacity: 0;
            animation: fadeIn 0.4s ease-out 0.4s forwards;
        }
        
        .login-link a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #c7d2fe;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Sign Up</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="cpf_no">CPF_NO</label>
                <input type="text" id="cpf_no" name="cpf_no" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-submit"><span>Sign Up</span></button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</body>
</html>
