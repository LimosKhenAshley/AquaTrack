<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            --primary-color: #0284c7;
            --primary-light: #38bdf8;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Bubbles */
        .bubbles { position: fixed; width: 100%; height: 100%; overflow: hidden; top: 0; left: 0; pointer-events: none; z-index: 0; }
        .bubble  { position: absolute; bottom: -100px; background: rgba(255,255,255,0.07); border-radius: 50%; animation: rise 10s infinite ease-in; }
        @keyframes rise {
            0%   { bottom: -100px; transform: translateX(0); }
            50%  { transform: translateX(100px); }
            100% { bottom: 1080px; transform: translateX(-200px); }
        }
        .bubble:nth-child(1) { left:5%;  width:80px;  height:80px;  animation-duration:8s; }
        .bubble:nth-child(2) { left:20%; width:40px;  height:40px;  animation-duration:12s; animation-delay:2s; }
        .bubble:nth-child(3) { left:50%; width:100px; height:100px; animation-duration:16s; animation-delay:4s; }
        .bubble:nth-child(4) { left:75%; width:60px;  height:60px;  animation-duration:10s; animation-delay:1s; }
        .bubble:nth-child(5) { left:88%; width:50px;  height:50px;  animation-duration:9s;  animation-delay:3s; }

        .page-wrapper { position: relative; z-index: 10; max-width: 760px; margin: 0 auto; }

        .back-nav { margin-bottom: 1.5rem; }
        .back-nav a {
            color: rgba(255,255,255,0.9); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-weight: 600; font-size: 0.95rem; transition: gap 0.2s ease;
        }
        .back-nav a:hover { gap: 0.75rem; color: #fff; }

        .privacy-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255,255,255,0.2);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .page-header { text-align: center; margin-bottom: 2.5rem; }
        .page-header .logo {
            width: 80px; height: 80px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 10px 30px rgba(2,132,199,0.4);
        }
        .page-header .logo i { font-size: 36px; color: white; }
        .page-header h1  { font-size: 26px; font-weight: 700; color: #2d3748; margin-bottom: 0.25rem; }
        .page-header .meta { color: #a0aec0; font-size: 0.82rem; }

        /* Table of contents */
        .toc {
            background: #f0f9ff; border-radius: 14px;
            padding: 1.25rem 1.5rem; margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }
        .toc h6 { font-weight: 700; color: var(--primary-color); margin-bottom: 0.75rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .toc ol  { margin: 0; padding-left: 1.25rem; }
        .toc li  { margin-bottom: 0.4rem; }
        .toc a   { color: #4a5568; text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
        .toc a:hover { color: var(--primary-color); }

        /* Sections */
        .privacy-section { margin-bottom: 2rem; scroll-margin-top: 20px; }

        .section-title {
            display: flex; align-items: center; gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .section-title .icon-box {
            width: 38px; height: 38px; min-width: 38px;
            background: var(--primary-gradient);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .section-title .icon-box i { font-size: 18px; color: white; }
        .section-title h2 { font-size: 1.05rem; font-weight: 700; color: #2d3748; margin: 0; }

        .privacy-section p,
        .privacy-section li { color: #4a5568; font-size: 0.92rem; line-height: 1.75; }

        .privacy-section ul { padding-left: 1.5rem; }
        .privacy-section ul li { margin-bottom: 0.4rem; }

        .highlight-box {
            background: #f0f9ff; border: 1.5px solid #bae6fd;
            border-radius: 12px; padding: 1rem 1.25rem; margin: 1rem 0;
        }
        .highlight-box p { margin: 0; font-size: 0.88rem; color: #0369a1; }

        hr.section-divider { border: none; border-top: 2px solid #edf2f7; margin: 2rem 0; }

        /* Contact strip */
        .contact-strip {
            background: linear-gradient(135deg, #eff8ff 0%, #e0f2fe 100%);
            border-radius: 14px; padding: 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            border: 1.5px solid #bae6fd; margin-top: 2rem;
        }
        .contact-strip .icon {
            width: 48px; height: 48px; min-width: 48px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .contact-strip .icon i { font-size: 22px; color: white; }
        .contact-strip h6 { font-weight: 700; color: #2d3748; margin-bottom: 0.2rem; }
        .contact-strip p  { margin: 0; color: #718096; font-size: 0.85rem; }
        .contact-strip a  { color: var(--primary-color); font-weight: 600; text-decoration: none; }
        .contact-strip a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="page-wrapper">
        <div class="back-nav">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
        </div>

        <div class="privacy-card">

            <div class="page-header">
                <div class="logo"><i class="bi bi-shield-check"></i></div>
                <h1>Privacy Policy</h1>
                <p class="meta">Last updated: <?= date('F j, Y') ?> &nbsp;·&nbsp; AquaTrack System</p>
            </div>

            <!-- Table of Contents -->
            <div class="toc">
                <h6><i class="bi bi-list-ul me-1"></i> Contents</h6>
                <ol>
                    <li><a href="#s1">Information We Collect</a></li>
                    <li><a href="#s2">How We Use Your Information</a></li>
                    <li><a href="#s3">Data Storage &amp; Security</a></li>
                    <li><a href="#s4">Cookies &amp; Session Data</a></li>
                    <li><a href="#s5">Data Sharing</a></li>
                    <li><a href="#s6">Your Rights</a></li>
                    <li><a href="#s7">Data Retention</a></li>
                    <li><a href="#s8">Changes to This Policy</a></li>
                    <li><a href="#s9">Contact Us</a></li>
                </ol>
            </div>

            <p style="color:#4a5568; font-size:0.92rem; line-height:1.75; margin-bottom:2rem;">
                AquaTrack ("we", "our", or "us") is committed to protecting your personal information. This Privacy Policy explains how we collect, use, store, and safeguard data when you use our water monitoring and management system.
            </p>

            <hr class="section-divider">

            <!-- Section 1 -->
            <div class="privacy-section" id="s1">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-collection"></i></div>
                    <h2>1. Information We Collect</h2>
                </div>
                <p>We collect the following categories of information:</p>
                <ul>
                    <li><strong>Account Information:</strong> Full name, email address, and role assignment provided during registration.</li>
                    <li><strong>Authentication Data:</strong> Hashed passwords (using bcrypt), session identifiers, and optional "Remember Me" tokens.</li>
                    <li><strong>Usage Data:</strong> Login timestamps, IP addresses, and audit log entries for security and accountability purposes.</li>
                    <li><strong>Operational Data:</strong> Water usage readings, reports, and records you submit or manage within the system.</li>
                </ul>
            </div>

            <hr class="section-divider">

            <!-- Section 2 -->
            <div class="privacy-section" id="s2">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-gear"></i></div>
                    <h2>2. How We Use Your Information</h2>
                </div>
                <p>Your information is used exclusively to:</p>
                <ul>
                    <li>Authenticate and authorize your access to the system.</li>
                    <li>Maintain security through account lockout and audit logging.</li>
                    <li>Provide role-based access to features and dashboards.</li>
                    <li>Send system notifications such as password reset emails.</li>
                    <li>Generate reports and analytics for operational management.</li>
                </ul>
                <div class="highlight-box">
                    <p><i class="bi bi-info-circle me-2"></i>We do <strong>not</strong> use your data for marketing, advertising, or any commercial profiling purposes.</p>
                </div>
            </div>

            <hr class="section-divider">

            <!-- Section 3 -->
            <div class="privacy-section" id="s3">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-database-lock"></i></div>
                    <h2>3. Data Storage &amp; Security</h2>
                </div>
                <p>We take data security seriously and implement the following safeguards:</p>
                <ul>
                    <li><strong>Passwords</strong> are stored as bcrypt hashes — never in plain text.</li>
                    <li><strong>Sessions</strong> are regenerated after login to prevent session fixation attacks.</li>
                    <li><strong>CSRF tokens</strong> are used on all forms to prevent cross-site request forgery.</li>
                    <li><strong>Account lockout</strong> is enforced after 5 failed login attempts.</li>
                    <li><strong>Database connections</strong> use parameterized queries to prevent SQL injection.</li>
                    <li>Data is stored on secured, access-controlled servers within our organization.</li>
                </ul>
            </div>

            <hr class="section-divider">

            <!-- Section 4 -->
            <div class="privacy-section" id="s4">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-cookie"></i></div>
                    <h2>4. Cookies &amp; Session Data</h2>
                </div>
                <p>AquaTrack uses the following types of cookies:</p>
                <ul>
                    <li><strong>Session Cookie (PHPSESSID):</strong> Required for authentication. Deleted when you close your browser.</li>
                    <li><strong>Remember Me Cookie:</strong> An optional, secure token stored for 30 days if you opt in on the login page. You can clear it by logging out.</li>
                </ul>
                <p>We do not use third-party analytics cookies or advertising cookies.</p>
            </div>

            <hr class="section-divider">

            <!-- Section 5 -->
            <div class="privacy-section" id="s5">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-share"></i></div>
                    <h2>5. Data Sharing</h2>
                </div>
                <p>We do <strong>not</strong> sell, rent, or share your personal data with third parties, except in the following limited circumstances:</p>
                <ul>
                    <li>When required to comply with applicable law or a valid legal order.</li>
                    <li>To protect the rights, property, or safety of our users or the organization.</li>
                    <li>With system administrators who require access to manage the platform.</li>
                </ul>
            </div>

            <hr class="section-divider">

            <!-- Section 6 -->
            <div class="privacy-section" id="s6">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-person-check"></i></div>
                    <h2>6. Your Rights</h2>
                </div>
                <p>You have the right to:</p>
                <ul>
                    <li><strong>Access</strong> the personal data we hold about you.</li>
                    <li><strong>Correct</strong> any inaccurate or incomplete information.</li>
                    <li><strong>Request deletion</strong> of your account and associated data.</li>
                    <li><strong>Withdraw consent</strong> for optional features like "Remember Me" at any time.</li>
                </ul>
                <p>To exercise these rights, please contact your system administrator or use our support channel.</p>
            </div>

            <hr class="section-divider">

            <!-- Section 7 -->
            <div class="privacy-section" id="s7">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-clock-history"></i></div>
                    <h2>7. Data Retention</h2>
                </div>
                <p>We retain your personal data for as long as your account remains active or as required by operational and legal obligations. Audit logs are retained for a minimum of 12 months. Upon account deletion, personal identifiers will be removed in accordance with our data retention schedule.</p>
            </div>

            <hr class="section-divider">

            <!-- Section 8 -->
            <div class="privacy-section" id="s8">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-arrow-repeat"></i></div>
                    <h2>8. Changes to This Policy</h2>
                </div>
                <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. We will notify users of significant changes by posting an updated policy on this page with a revised "Last updated" date. Continued use of AquaTrack after changes constitutes acceptance of the updated policy.</p>
            </div>

            <hr class="section-divider">

            <!-- Section 9 / Contact -->
            <div class="privacy-section" id="s9">
                <div class="section-title">
                    <div class="icon-box"><i class="bi bi-envelope"></i></div>
                    <h2>9. Contact Us</h2>
                </div>
                <p>If you have questions or concerns about this Privacy Policy or how your data is handled, please reach out:</p>
            </div>

            <div class="contact-strip">
                <div class="icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h6>AquaTrack Data Privacy Officer</h6>
                    <p>Email: <a href="mailto:privacy@aquatrack.com">privacy@aquatrack.com</a> &nbsp;·&nbsp; Or visit our <a href="need_help.php">Help &amp; Support</a> page.</p>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for TOC links
        document.querySelectorAll('.toc a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>