<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Maintenance - {{ config('app.name') }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: white;
                padding: 20px;
            }

            .maintenance-container {
                text-align: center;
                max-width: 600px;
                animation: fadeIn 0.8s ease-in;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .maintenance-icon {
                font-size: 8rem;
                margin-bottom: 2rem;
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {

                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.1);
                }
            }

            .maintenance-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            }

            .maintenance-subtitle {
                font-size: 1.2rem;
                margin-bottom: 2rem;
                opacity: 0.9;
            }

            .maintenance-card {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 2rem;
                margin-top: 2rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .feature-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.25);
                padding: 0.5rem 1rem;
                border-radius: 25px;
                margin: 0.5rem;
                font-size: 0.9rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
            }

            .progress-container {
                margin-top: 2rem;
            }

            .progress {
                height: 8px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 10px;
                overflow: hidden;
            }

            .progress-bar {
                background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
                animation: progress 3s ease-in-out infinite;
            }

            @keyframes progress {
                0% {
                    width: 0%;
                }

                50% {
                    width: 100%;
                }

                100% {
                    width: 0%;
                }
            }

            .footer-note {
                margin-top: 3rem;
                font-size: 0.9rem;
                opacity: 0.7;
            }

            @media (max-width: 576px) {
                .maintenance-title {
                    font-size: 2rem;
                }

                .maintenance-icon {
                    font-size: 5rem;
                }
            }
        </style>
    </head>

    <body>
        <div class="maintenance-container">
            <div class="maintenance-icon">
                <i class="bi bi-tools"></i>
            </div>

            <h1 class="maintenance-title">System Under Maintenance</h1>
            <p class="maintenance-subtitle">
                We're upgrading our system to serve you better
            </p>

            <div class="maintenance-card">
                <h5 class="mb-3">
                    <i class="bi bi-clock me-2"></i>Expected Duration
                </h5>
                <p class="fs-4 fw-bold mb-0">10 - 30 Minutes</p>
            </div>

            <div class="maintenance-card mt-3">
                <h5 class="mb-3">
                    <i class="bi bi-stars me-2"></i>What's Coming
                </h5>
                <div class="feature-badge">
                    <i class="bi bi-speedometer2 me-1"></i> Performance Improvements
                </div>
                <div class="feature-badge">
                    <i class="bi bi-shield-check me-1"></i> Security Updates
                </div>
                <div class="feature-badge">
                    <i class="bi bi-gear me-1"></i> New Features
                </div>
            </div>

            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar" role="progressbar"></div>
                </div>
                <small class="d-block mt-2 opacity-75">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Upgrading in progress...
                </small>
            </div>

            <div class="maintenance-card mt-4">
                <h6 class="mb-2">Need Urgent Acces?</h6>
                <p class="mb-0">
                    <i class="bi bi-envelope me-2"></i>
                    Contact IT Support: <strong>dcmit55@gmail.com</strong>
                </p>
            </div>

            <div class="footer-note">
                <p class="mb-0">
                    {{ config('app.name') }} &copy; {{ date('Y') }}
                </p>
                <small>Version {{ config('app.version', '2.0') }}</small>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Auto refresh every 30 seconds
            setTimeout(() => location.reload(), 30000);

            // Check if maintenance is lifted
            setInterval(() => {
                fetch(window.location.href, {
                        method: 'HEAD'
                    })
                    .then(response => {
                        if (response.ok && response.status === 200) {
                            location.reload();
                        }
                    })
                    .catch(() => {});
            }, 10000);
        </script>
    </body>

</html>
