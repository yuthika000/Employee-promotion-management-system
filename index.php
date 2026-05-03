<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROMO TRACKER</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0d0d1a 100%);
            background-color: #0f0f23;
            min-height: 100vh;
            overflow-x: hidden;
            color: #fff;
        }
        
        /* Animated background orbs */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            pointer-events: none;
            z-index: 0;
        }
        
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #6366f1;
            top: -100px;
            left: -100px;
            animation: float1 15s ease-in-out infinite;
        }
        
        .orb-2 {
            width: 300px;
            height: 300px;
            background: #a855f7;
            top: 50%;
            right: -50px;
            animation: float2 18s ease-in-out infinite;
        }
        
        .orb-3 {
            width: 350px;
            height: 350px;
            background: #3b82f6;
            bottom: -100px;
            left: 30%;
            animation: float3 20s ease-in-out infinite;
        }
        
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(100px, 50px) scale(1.1); }
        }
        
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-80px, 60px) scale(1.15); }
        }
        
        @keyframes float3 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, -40px) scale(1.05); }
        }
        
        /* Hero Section */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px 20px 60px;
        }
        
        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            max-width: 1200px;
            width: 100%;
        }
        
        .hero-text {
            text-align: left;
        }
        
        .hero-image {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: floatImage 6s ease-in-out infinite;
        }
        
        .hero-image svg {
            width: 100%;
            max-width: 500px;
            height: auto;
        }
        
        @keyframes floatImage {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -0.03em;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.6);
            max-width: 600px;
            margin-bottom: 40px;
            line-height: 1.6;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out 0.1s forwards;
        }
        
        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out 0.2s forwards;
        }
        
        .hero-btn {
            padding: 16px 40px;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .hero-btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }
        
        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
        }
        
        .hero-btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
        }
        
        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Features Section */
        .features {
            position: relative;
            z-index: 1;
            padding: 100px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }
        
        .section-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--card-gradient-1) 0%, var(--card-gradient-2) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }
        
        .feature-card:hover::before {
            opacity: 0.1;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .feature-card-1 { --card-gradient-1: #6366f1; --card-gradient-2: #8b5cf6; }
        .feature-card-1 .feature-icon { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        
        .feature-card-2 { --card-gradient-1: #0ea5e9; --card-gradient-2: #3b82f6; }
        .feature-card-2 .feature-icon { background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%); }
        
        .feature-card-3 { --card-gradient-1: #10b981; --card-gradient-2: #059669; }
        .feature-card-3 .feature-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        
        .feature-card-4 { --card-gradient-1: #f59e0b; --card-gradient-2: #d97706; }
        .feature-card-4 .feature-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }
            
            .hero-text {
                text-align: center;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-image svg {
                max-width: 350px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Orbs -->
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Modern <span>Employee Promotion</span><br>Management System</h1>
                <p>Streamline your HR operations with our powerful, intuitive platform. Manage employees, departments, designations, and more with ease.</p>
                <div class="hero-buttons">
                    <a href="signup.php" class="hero-btn hero-btn-primary">
                        <span>Sign Up</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="login.php" class="hero-btn hero-btn-secondary">
                        <span>Sign In</span>
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <svg viewBox="0 0 600 450" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- Gradients for isometric blocks -->
                        <linearGradient id="step1Top" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6"/>
                            <stop offset="100%" style="stop-color:#60a5fa"/>
                        </linearGradient>
                        <linearGradient id="step1Side" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#2563eb"/>
                            <stop offset="100%" style="stop-color:#1d4ed8"/>
                        </linearGradient>
                        <linearGradient id="step2Top" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#f87171"/>
                            <stop offset="100%" style="stop-color:#ef4444"/>
                        </linearGradient>
                        <linearGradient id="step2Side" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#dc2626"/>
                            <stop offset="100%" style="stop-color:#b91c1c"/>
                        </linearGradient>
                        <linearGradient id="step3Top" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#fbbf24"/>
                            <stop offset="100%" style="stop-color:#f59e0b"/>
                        </linearGradient>
                        <linearGradient id="step3Side" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#d97706"/>
                            <stop offset="100%" style="stop-color:#b45309"/>
                        </linearGradient>
                        <linearGradient id="step4Top" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#86efac"/>
                            <stop offset="100%" style="stop-color:#4ade80"/>
                        </linearGradient>
                        <linearGradient id="step4Side" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#22c55e"/>
                            <stop offset="100%" style="stop-color:#16a34a"/>
                        </linearGradient>
                        <linearGradient id="bubbleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#0ea5e9"/>
                            <stop offset="100%" style="stop-color:#0284c7"/>
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="2" dy="4" stdDeviation="3" flood-opacity="0.3"/>
                        </filter>
                    </defs>

                    <!-- Step 1 - Blue (lowest) -->
                    <!-- Top face -->
                    <path d="M 120 380 L 180 350 L 240 380 L 180 410 Z" fill="url(#step1Top)"/>
                    <!-- Left face -->
                    <path d="M 120 380 L 180 410 L 180 450 L 120 420 Z" fill="url(#step1Side)"/>
                    <!-- Right face -->
                    <path d="M 240 380 L 180 410 L 180 450 L 240 420 Z" fill="#1e40af"/>

                    <!-- Step 2 - Red -->
                    <!-- Top face -->
                    <path d="M 200 340 L 260 310 L 320 340 L 260 370 Z" fill="url(#step2Top)"/>
                    <!-- Left face -->
                    <path d="M 200 340 L 260 370 L 260 410 L 200 380 Z" fill="url(#step2Side)"/>
                    <!-- Right face -->
                    <path d="M 320 340 L 260 370 L 260 410 L 320 380 Z" fill="#991b1b"/>

                    <!-- Step 3 - Orange -->
                    <!-- Top face -->
                    <path d="M 280 300 L 340 270 L 400 300 L 340 330 Z" fill="url(#step3Top)"/>
                    <!-- Left face -->
                    <path d="M 280 300 L 340 330 L 340 370 L 280 340 Z" fill="url(#step3Side)"/>
                    <!-- Right face -->
                    <path d="M 400 300 L 340 330 L 340 370 L 400 340 Z" fill="#92400e"/>

                    <!-- Step 4 - Green (highest with flag) -->
                    <!-- Top face -->
                    <path d="M 360 260 L 420 230 L 480 260 L 420 290 Z" fill="url(#step4Top)"/>
                    <!-- Left face -->
                    <path d="M 360 260 L 420 290 L 420 330 L 360 300 Z" fill="url(#step4Side)"/>
                    <!-- Right face -->
                    <path d="M 480 260 L 420 290 L 420 330 L 480 300 Z" fill="#15803d"/>

                    <!-- Flag pole -->
                    <line x1="435" y1="230" x2="435" y2="140" stroke="#475569" stroke-width="4"/>
                    <!-- Flag -->
                    <path d="M 435 150 Q 465 145 485 155 Q 465 165 435 160 Z" fill="#64748b"/>

                    <!-- Man figure (climbing steps) - Red outfit -->
                    <g transform="translate(240, 210)">
                        <!-- Left leg -->
                        <path d="M 15 80 Q 10 100 5 115" stroke="#dc2626" stroke-width="8" fill="none" stroke-linecap="round"/>
                        <!-- Right leg (raised on step) -->
                        <path d="M 25 80 Q 35 95 45 90" stroke="#dc2626" stroke-width="8" fill="none" stroke-linecap="round"/>
                        <!-- Body -->
                        <ellipse cx="20" cy="55" rx="15" ry="25" fill="#dc2626"/>
                        <!-- Head -->
                        <circle cx="20" cy="25" r="12" fill="#fca5a5"/>
                        <!-- Hair -->
                        <path d="M 8 20 Q 20 8 32 20 Q 30 15 20 12 Q 10 15 8 20" fill="#1f2937"/>
                        <!-- Left arm (raised) -->
                        <path d="M 12 45 Q -5 35 -10 20" stroke="#dc2626" stroke-width="6" fill="none" stroke-linecap="round"/>
                        <!-- Right arm -->
                        <path d="M 28 45 Q 45 40 50 25" stroke="#dc2626" stroke-width="6" fill="none" stroke-linecap="round"/>
                    </g>

                    <!-- Woman figure (on higher step) - Orange outfit -->
                    <g transform="translate(330, 155)">
                        <!-- Left leg -->
                        <path d="M 15 75 Q 10 90 5 100" stroke="#f97316" stroke-width="7" fill="none" stroke-linecap="round"/>
                        <!-- Right leg (raised) -->
                        <path d="M 25 75 Q 35 85 45 75" stroke="#f97316" stroke-width="7" fill="none" stroke-linecap="round"/>
                        <!-- Body/Dress -->
                        <path d="M 5 55 L 35 55 L 40 75 L 0 75 Z" fill="#f97316"/>
                        <!-- Head -->
                        <circle cx="20" cy="40" r="11" fill="#fdba74"/>
                        <!-- Hair -->
                        <ellipse cx="20" cy="35" rx="14" ry="10" fill="#1f2937"/>
                        <path d="M 8 35 Q 5 55 12 65" stroke="#1f2937" stroke-width="4" fill="none"/>
                        <!-- Left arm (raised to flag) -->
                        <path d="M 10 50 Q -5 40 -15 25" stroke="#f97316" stroke-width="5" fill="none" stroke-linecap="round"/>
                        <!-- Right arm -->
                        <path d="M 30 50 Q 45 35 50 20" stroke="#f97316" stroke-width="5" fill="none" stroke-linecap="round"/>
                    </g>

                    <!-- Speech Bubble -->
                    <g filter="url(#shadow)">
                        <!-- Main bubble shape -->
                        <path d="M 50 80 
                                 L 200 80 
                                 L 220 100 
                                 L 220 160 
                                 L 200 180
                                 L 120 180
                                 L 100 210
                                 L 90 180
                                 L 50 180
                                 L 30 160
                                 L 30 100
                                 L 50 80 Z" 
                              fill="url(#bubbleGrad)"/>
                        <!-- Tail of bubble -->
                        <path d="M 100 180 L 110 200 L 120 180" fill="url(#bubbleGrad)"/>
                    </g>

                    <!-- Text in bubble -->
                    <text x="125" y="115" fill="white" font-size="16" font-weight="700" text-anchor="middle">PROMO TRACKER</text>
                    <text x="125" y="135" fill="white" font-size="10" text-anchor="middle">Morden Employee</text>
                    <text x="125" y="148" fill="white" font-size="10" text-anchor="middle">Promotion</text>
                    <text x="125" y="161" fill="white" font-size="10" text-anchor="middle">Management System</text>

                    <!-- Floating decorative elements -->
                    <circle cx="520" cy="100" r="8" fill="#6366f1" opacity="0.6"/>
                    <circle cx="50" cy="250" r="6" fill="#a855f7" opacity="0.5"/>
                    <circle cx="550" cy="350" r="10" fill="#3b82f6" opacity="0.4"/>
                    <circle cx="80" cy="40" r="5" fill="#10b981" opacity="0.5"/>
                </svg>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="section-header">
            <h2>Powerful Features</h2>
            <p>Everything you need to manage your workforce efficiently</p>
        </div>
        <div class="features-grid">
            <div class="feature-card feature-card-1">
                <div class="feature-icon">👥</div>
                <h3>Employee Management</h3>
                <p>Comprehensive employee records with detailed information, easy search, and quick access to personnel data.</p>
            </div>
            <div class="feature-card feature-card-2">
                <div class="feature-icon">🏢</div>
                <h3>Department Organization</h3>
                <p>Organize employees by departments with automatic validation and synchronization with lookup tables.</p>
            </div>
            <div class="feature-card feature-card-3">
                <div class="feature-icon">🔄</div>
                <h3>Data Synchronization</h3>
                <p>Seamless sync between main tables and master tables with mismatch detection and auto-correction.</p>
            </div>
            <div class="feature-card feature-card-4">
                <div class="feature-icon">📊</div>
                <h3>Real-time Analytics</h3>
                <p>Live dashboard with statistics and insights to make informed HR decisions.</p>
            </div>
        </div>
    </section>
</body>
</html>
