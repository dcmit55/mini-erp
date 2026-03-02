<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                --error-gradient: linear-gradient(135deg, #f13e3e 0%, #a1014c 100%);
                --warning-gradient: linear-gradient(135deg, #fbb034 0%, #ff4e00 100%);
                --success-gradient: linear-gradient(135deg, #48cae4 0%, #023e8a 100%);
            }

            body {
                font-family: 'Inter', sans-serif;
                background: var(--primary-gradient);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
                position: relative;
            }

            /* Dynamic background based on error type */
            .status-3xx body {
                background: var(--success-gradient);
                background-size: 400% 400%;
                animation: gradientFlow 8s ease infinite;
            }

            .status-4xx body {
                background: var(--error-gradient);
                background-size: 400% 400%;
                animation: gradientFlow 8s ease infinite;
            }

            .status-5xx body {
                background: var(--warning-gradient);
                background-size: 400% 400%;
                animation: gradientFlow 8s ease infinite;
            }

            @keyframes gradientFlow {
                0% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }

                100% {
                    background-position: 0% 50%;
                }
            }

            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
                z-index: -1;
            }

            .error-container {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                border-radius: 24px;
                box-shadow:
                    0 32px 64px rgba(0, 0, 0, 0.15),
                    0 16px 32px rgba(0, 0, 0, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.8);
                padding: 3rem 2.5rem;
                text-align: center;
                max-width: 520px;
                width: 100%;
                position: relative;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.3);
            }

            .error-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 5px;
                background: var(--primary-gradient);
                background-size: 300% 100%;
                animation: gradientShift 4s ease infinite;
            }

            .error-container::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 100%;
                height: 100%;
                background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
                pointer-events: none;
                z-index: -1;
            }

            @keyframes gradientShift {

                0%,
                100% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }
            }

            .error-icon-wrapper {
                position: relative;
                display: inline-block;
                margin-bottom: 1.5rem;
            }

            .error-icon {
                font-size: 4rem;
                background: var(--primary-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                animation: iconPulse 3s ease-in-out infinite;
                position: relative;
                font-family: 'Font Awesome 6 Free';
                font-weight: 900;
                /* Untuk solid icon */
            }

            /* link */
            .error-icon.icon-415::before {
                content: '\f15b';
            }

            /* bug */
            .error-icon.icon-429::before {
                content: '\f0e4';
            }

            /* gauge */

            @keyframes iconPulse {

                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.05);
                }
            }

            .error-icon-bg {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 120px;
                height: 120px;
                background: var(--primary-gradient);
                border-radius: 50%;
                opacity: 0.1;
                z-index: -1;
                animation: bgPulse 3s ease-in-out infinite;
            }

            @keyframes bgPulse {

                0%,
                100% {
                    transform: translate(-50%, -50%) scale(1);
                    opacity: 0.1;
                }

                50% {
                    transform: translate(-50%, -50%) scale(1.2);
                    opacity: 0.05;
                }
            }

            .error-code {
                font-size: 4.5rem;
                font-weight: 800;
                background: var(--primary-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin-bottom: 1rem;
                font-family: 'Poppins', sans-serif;
                letter-spacing: -0.02em;
                text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .error-title {
                font-size: 1.75rem;
                font-weight: 600;
                color: #2d3748;
                margin-bottom: 1rem;
                letter-spacing: -0.01em;
            }

            .error-message {
                font-size: 1.1rem;
                color: #718096;
                margin-bottom: 2.5rem;
                line-height: 1.7;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }

            .error-actions {
                display: flex;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
            }

            .btn-home {
                background: var(--primary-gradient);
                border: none;
                color: white;
                padding: 14px 32px;
                border-radius: 50px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                font-size: 0.95rem;
                letter-spacing: 0.01em;
                position: relative;
                overflow: hidden;
            }

            .btn-home::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .btn-home:hover::before {
                left: 100%;
            }

            .btn-home:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
                color: white;
            }

            .btn-back {
                background: rgba(255, 255, 255, 0.9);
                border: 2px solid rgba(102, 126, 234, 0.2);
                color: #667eea;
                padding: 12px 28px;
                border-radius: 50px;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-size: 0.95rem;
                backdrop-filter: blur(10px);
            }

            .btn-back:hover {
                border-color: #667eea;
                background: rgba(102, 126, 234, 0.05);
                color: #5a67d8;
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            }

            .floating-shapes {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                overflow: hidden;
                z-index: -1;
                pointer-events: none;
            }

            .shape {
                position: absolute;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                animation: float 6s ease-in-out infinite;
            }

            .shape:nth-child(1) {
                width: 80px;
                height: 80px;
                top: 20%;
                left: 10%;
                animation-delay: 0s;
            }

            .shape:nth-child(2) {
                width: 60px;
                height: 60px;
                top: 60%;
                right: 15%;
                animation-delay: 2s;
            }

            .shape:nth-child(3) {
                width: 40px;
                height: 40px;
                bottom: 20%;
                left: 20%;
                animation-delay: 4s;
            }

            @keyframes float {

                0%,
                100% {
                    transform: translateY(0px) rotate(0deg);
                }

                33% {
                    transform: translateY(-20px) rotate(120deg);
                }

                66% {
                    transform: translateY(10px) rotate(240deg);
                }
            }

            /* Status-specific styles */
            .status-3xx .error-container::before {
                background: var(--success-gradient);
            }

            .status-3xx .error-code,
            .status-3xx .error-icon {
                background: var(--success-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .status-3xx .btn-home {
                background: var(--success-gradient);
                box-shadow: 0 8px 25px rgba(72, 202, 228, 0.3);
            }

            .status-3xx .btn-home:hover {
                box-shadow: 0 12px 35px rgba(72, 202, 228, 0.4);
            }

            .status-3xx .error-icon-bg {
                background: var(--success-gradient);
            }

            .status-4xx .error-container::before {
                background: var(--error-gradient);
            }

            .status-4xx .error-code,
            .status-4xx .error-icon {
                background: var(--error-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .status-4xx .btn-home {
                background: var(--error-gradient);
                box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
            }

            .status-4xx .btn-home:hover {
                box-shadow: 0 12px 35px rgba(255, 107, 107, 0.4);
            }

            .status-4xx .error-icon-bg {
                background: var(--error-gradient);
            }

            .status-5xx .error-container::before {
                background: var(--warning-gradient);
            }

            .status-5xx .error-code,
            .status-5xx .error-icon {
                background: var(--warning-gradient);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .status-5xx .btn-home {
                background: var(--warning-gradient);
                box-shadow: 0 8px 25px rgba(251, 176, 52, 0.35), 0 4px 12px rgba(255, 78, 0, 0.18);
            }

            .status-5xx .btn-home:hover {
                box-shadow: 0 12px 35px rgba(255, 78, 0, 0.35);
            }

            .status-5xx .error-icon-bg {
                background: var(--warning-gradient);
                opacity: 0.18;
            }

            @media (max-width: 768px) {
                .error-container {
                    padding: 2.5rem 2rem;
                    margin: 1rem;
                    border-radius: 20px;
                }

                .error-code {
                    font-size: 3.5rem;
                }

                .error-title {
                    font-size: 1.5rem;
                }

                .error-message {
                    font-size: 1rem;
                }

                .error-actions {
                    flex-direction: column;
                    gap: 15px;
                }

                .btn-home,
                .btn-back {
                    width: 100%;
                    justify-content: center;
                    max-width: 280px;
                }

                .error-icon {
                    font-size: 3rem;
                }
            }

            @media (max-width: 480px) {
                body {
                    padding: 15px;
                }

                .error-container {
                    padding: 2rem 1.5rem;
                }

                .error-code {
                    font-size: 3rem;
                }

                .error-title {
                    font-size: 1.3rem;
                }
            }
        </style>
    </head>

    <body
        class="status-@php
$code = (int) View::yieldContent('code', 404);
        if ($code >= 300 && $code < 400) echo '3xx';
        elseif ($code >= 400 && $code < 500) echo '4xx';
        elseif ($code >= 500 && $code < 600) echo '5xx';
        else echo '4xx'; @endphp">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="error-container">
            <div class="error-icon-wrapper">
                <div class="error-icon-bg"></div>
                <div class="error-icon icon-@yield('code', '404')">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>

            <div class="error-code">@yield('code')</div>

            <h1 class="error-title">@yield('title')</h1>

            <p class="error-message">@yield('message')</p>

            <div class="error-actions">
                <a href="javascript:history.back()" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
                <a href="{{ url('/dashboard') }}" class="btn-home">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Add dynamic icon based on error code
            document.addEventListener('DOMContentLoaded', function() {
                const errorCode = '@yield('code', '404')';
                const iconElement = document.querySelector('.error-icon i');

                const iconMap = {
                    // 3xx Redirect Errors
                    '300': 'fa-list-ul',
                    '301': 'fa-share',
                    '302': 'fa-directions',
                    '303': 'fa-external-link-alt',
                    '304': 'fa-check-circle',
                    '305': 'fa-shield-alt',
                    '306': 'fa-exclamation-triangle',
                    '307': 'fa-route',
                    '308': 'fa-share-square',
                    '310': 'fa-copy',

                    // 4xx Client Errors
                    '400': 'fa-exclamation-triangle',
                    '401': 'fa-lock',
                    '403': 'fa-ban',
                    '404': 'fa-search',
                    '405': 'fa-hand-paper',
                    '406': 'fa-times-circle',
                    '408': 'fa-clock',
                    '409': 'fa-exclamation-triangle',
                    '410': 'fa-trash',
                    '411': 'fa-ruler',
                    '413': 'fa-weight-hanging',
                    '414': 'fa-link',
                    '415': 'fa-file-times',
                    '422': 'fa-bug',
                    '429': 'fa-tachometer-alt',

                    // 5xx Server Errors
                    '500': 'fa-server',
                    '501': 'fa-tools',
                    '502': 'fa-unlink',
                    '503': 'fa-wrench',
                    '504': 'fa-hourglass-half',
                    '505': 'fa-code',
                    '507': 'fa-hdd',
                    '508': 'fa-sync',
                    '511': 'fa-wifi'
                };

                if (iconMap[errorCode]) {
                    iconElement.className = `fas ${iconMap[errorCode]}`;
                }
            });
        </script>
    </body>

</html>
