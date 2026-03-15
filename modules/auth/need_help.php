<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Need Help – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            --primary-color: #0284c7;
            --primary-light: #38bdf8;
            --water-dark: #0369a1;
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
        .bubble { position: absolute; bottom: -100px; background: rgba(255,255,255,0.08); border-radius: 50%; animation: rise 10s infinite ease-in; }
        @keyframes rise {
            0%   { bottom: -100px; transform: translateX(0); }
            50%  { transform: translateX(100px); }
            100% { bottom: 1080px; transform: translateX(-200px); }
        }
        .bubble:nth-child(1) { left:10%; width:80px;  height:80px;  animation-duration:8s; }
        .bubble:nth-child(2) { left:25%; width:40px;  height:40px;  animation-duration:12s; animation-delay:2s; }
        .bubble:nth-child(3) { left:50%; width:100px; height:100px; animation-duration:16s; animation-delay:4s; }
        .bubble:nth-child(4) { left:70%; width:60px;  height:60px;  animation-duration:10s; animation-delay:1s; }
        .bubble:nth-child(5) { left:85%; width:50px;  height:50px;  animation-duration:9s;  animation-delay:3s; }

        .page-wrapper { position: relative; z-index: 10; max-width: 760px; margin: 0 auto; }

        /* Back nav */
        .back-nav { margin-bottom: 1.5rem; }
        .back-nav a {
            color: rgba(255,255,255,0.9); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-weight: 600; font-size: 0.95rem; transition: gap 0.2s ease;
        }
        .back-nav a:hover { gap: 0.75rem; color: #fff; }

        /* Card */
        .help-card {
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
        .page-header h1 { font-size: 26px; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .page-header p  { color: #718096; font-size: 0.95rem; }

        /* FAQ */
        .section-title {
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--primary-color);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }
        .section-title::after { content:''; flex:1; height:2px; background:#edf2f7; border-radius:2px; }

        .accordion-button {
            font-weight: 600; color: #2d3748; background: #f7fafc;
            border-radius: 12px !important; font-size: 0.95rem;
        }
        .accordion-button:not(.collapsed) { color: var(--primary-color); background: #eff8ff; box-shadow: none; }
        .accordion-button::after { filter: none; }
        .accordion-button:focus { box-shadow: none; }
        .accordion-item { border: 2px solid #edf2f7; border-radius: 12px !important; margin-bottom: 0.75rem; overflow: hidden; }
        .accordion-body { color: #4a5568; font-size: 0.92rem; line-height: 1.7; padding-top: 0.75rem; }

        /* Contact cards */
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1rem; }

        .contact-card {
            background: #f7fafc; border-radius: 16px;
            padding: 1.5rem 1rem; text-align: center;
            border: 2px solid #edf2f7; transition: all 0.3s ease;
            text-decoration: none; color: inherit; display: block;
        }
        .contact-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(2,132,199,0.15);
            color: inherit;
        }
        .contact-card .icon {
            width: 52px; height: 52px;
            background: var(--primary-gradient);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.875rem;
        }
        .contact-card .icon i { font-size: 24px; color: white; }
        .contact-card h6 { font-weight: 700; color: #2d3748; margin-bottom: 0.25rem; font-size: 0.95rem; }
        .contact-card p  { color: #718096; font-size: 0.8rem; margin: 0; }

        /* Contact form */
        .form-label {
            font-weight: 600; color: #4a5568; font-size: 0.85rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-control, .form-select {
            border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 0.7rem 1rem; font-size: 0.95rem; transition: border-color 0.3s;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: none; }

        .btn-submit {
            background: var(--primary-gradient);
            color: white; font-weight: 700;
            padding: 0.8rem 2rem; border-radius: 12px;
            font-size: 0.95rem; letter-spacing: 0.5px;
            text-transform: uppercase; border: none;
            transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(2,132,199,0.4); color: white; }

        .alert-success-custom {
            background: #f0fff4; border: 2px solid #68d391;
            border-radius: 12px; padding: 1rem; display: none;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: #f0fff4; color: #38a169;
            border: 1.5px solid #68d391; border-radius: 20px;
            font-size: 0.8rem; font-weight: 600;
            padding: 0.35rem 0.85rem; margin-bottom: 2rem;
        }
        .status-dot { width: 8px; height: 8px; background: #48bb78; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:0.6;transform:scale(1.3);} }

        hr.section-divider { border: none; border-top: 2px solid #edf2f7; margin: 2rem 0; }
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="page-wrapper">
        <!-- Back navigation -->
        <div class="back-nav">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
        </div>

        <div class="help-card">

            <div class="page-header">
                <div class="logo"><i class="bi bi-headset"></i></div>
                <h1>Help &amp; Support</h1>
                <p>We're here to help you get the most out of AquaTrack</p>
                <div class="status-badge">
                    <span class="status-dot"></span> Support is currently online
                </div>
            </div>

            <!-- FAQ -->
            <div class="section-title"><i class="bi bi-question-circle"></i> Frequently Asked Questions</div>

            <div class="accordion mb-4" id="faqAccordion">

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click the <strong>Forgot Password?</strong> link on the sign-in page, enter your registered email address, and we'll send you a reset link valid for 1 hour. Check your spam folder if you don't see it within a few minutes.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Why is my account locked?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            For security, accounts are temporarily locked for <strong>10 minutes</strong> after 5 consecutive failed login attempts. After that time, you may try again or use the Forgot Password option to regain access immediately.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How do I create a new account?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click <strong>Register</strong> on the sign-in page and fill in your details. Your account will be reviewed by an administrator before activation. You'll receive an email confirmation once your account is active.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            What does "Remember Me" do?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Checking <strong>Remember Me</strong> saves a secure token in your browser for 30 days, so you won't need to sign in on every visit from the same device. We recommend only using this on personal devices.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            My account is showing as inactive. What should I do?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Inactive accounts must be re-activated by an administrator. Please contact your system administrator or use the form below to reach our support team.
                        </div>
                    </div>
                </div>

            </div>

            <hr class="section-divider">

            <!-- Contact Options -->
            <div class="section-title"><i class="bi bi-telephone"></i> Contact Us</div>
            <div class="contact-grid mb-4">
                <a href="mailto:support@aquatrack.com" class="contact-card">
                    <div class="icon"><i class="bi bi-envelope"></i></div>
                    <h6>Email Support</h6>
                    <p>support@aquatrack.com</p>
                </a>
                <a href="tel:+1234567890" class="contact-card">
                    <div class="icon"><i class="bi bi-telephone"></i></div>
                    <h6>Phone Support</h6>
                    <p>Mon–Fri, 8am–5pm</p>
                </a>
                <a href="#contactForm" class="contact-card">
                    <div class="icon"><i class="bi bi-chat-dots"></i></div>
                    <h6>Support Ticket</h6>
                    <p>Response within 24h</p>
                </a>
            </div>

            <hr class="section-divider">

            <!-- Contact Form -->
            <div class="section-title" id="contactForm"><i class="bi bi-send"></i> Send a Message</div>

            <div class="alert-success-custom" id="successAlert">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong>Message sent!</strong> We'll get back to you within 24 hours.
            </div>

            <form id="helpForm" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" placeholder="Juan dela Cruz" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" placeholder="you@example.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Issue Type</label>
                        <select class="form-select">
                            <option value="">Select a category...</option>
                            <option>Login / Account Access</option>
                            <option>Password Reset</option>
                            <option>Account Registration</option>
                            <option>Technical Error</option>
                            <option>Feature Request</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" rows="4" placeholder="Describe your issue or question in detail..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-send me-2"></i>Send Message
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('helpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const successAlert = document.getElementById('successAlert');
            successAlert.style.display = 'block';
            this.reset();
            successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => successAlert.style.display = 'none', 6000);
        });
    </script>
</body>
</html>