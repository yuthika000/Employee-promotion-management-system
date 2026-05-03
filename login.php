<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cpf_no = trim($_POST['cpf_no']);
    $password = $_POST['password'];
    
    if (empty($cpf_no) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $sql = "SELECT id, cpf_no, password FROM users WHERE cpf_no = :cpf_no";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':cpf_no', $cpf_no);
        oci_execute($stid);
        
        $user = oci_fetch_assoc($stid);
        
        if ($user && password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['cpf_no'] = $user['CPF_NO'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid CPF_NO or password";
        }
        
        oci_free_statement($stid);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
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
        
        .login-container {
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
        
        .login-container h2 {
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
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .btn-submit:hover::before { opacity: 0.2; }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.2);
        }
        
        .btn-submit:active { transform: translateY(0) scale(0.98); }
        
        .btn-submit span { position: relative; z-index: 1; }
        
        @keyframes buttonPopIn {
            0% { opacity: 0; transform: scale(0.9) translateY(10px); }
            70% { transform: scale(1.02) translateY(-2px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            opacity: 0;
            animation: fadeIn 0.4s ease-out 0.4s forwards;
        }
        
        .signup-link a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .signup-link a:hover {
            color: #c7d2fe;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Sign In</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="cpf_no">CPF_NO</label>
                <input type="text" id="cpf_no" name="cpf_no" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit"><span>Sign In</span></button>
        </form>
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>
