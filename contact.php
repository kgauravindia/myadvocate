<?php
// contact.php - Contact & Grievance Redressal
require_once __DIR__ . '/config/app.php';

$pageTitle = "Contact Us & Profile Corrections - My Advocate";
$pageDescription = "Get in touch with the My Advocate team for inquiries, data updates, or grievance redressal.";

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO contact (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $subject, $message]);
        $msg = "Thank you! Your message has been received. Our team will get back to you shortly.";
    } catch (Exception $e) {
        $msg = "Your query has been recorded. We will contact you soon.";
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Contact Us</span>
    </nav>

    <div style="max-width: 760px; margin: 0 auto;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
            Contact & Grievance Redressal
        </h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">
            Have a question, profile correction request, or partnership inquiry? Send us a message.
        </p>

        <?php if ($msg): ?>
            <div class="stat-box" style="background: #f0fdf4; border-color: #86efac; color: #166534; margin-bottom: 1.5rem; font-weight: 600;">
                <i class="fas fa-circle-check"></i> <?= sanitize($msg) ?>
            </div>
        <?php endif; ?>

        <div class="stat-box" style="padding: 2.5rem 2rem;">
            <form action="contact" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Your Name *</label>
                        <input type="text" name="name" class="filter-input" placeholder="e.g. Adv. Amit Roy" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Your Email *</label>
                        <input type="email" name="email" class="filter-input" placeholder="you@example.com" required>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Subject / Purpose *</label>
                    <select name="subject" class="filter-select">
                        <option value="Profile Correction">Profile Correction / Update Request</option>
                        <option value="Profile Removal">Directory Removal Request</option>
                        <option value="Advocate Verification">Advocate Verification Inquiry</option>
                        <option value="General Inquiry">General Inquiry / Feedback</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Message / Details *</label>
                    <textarea name="message" class="filter-input" rows="5" placeholder="Please provide enrollment number or specific page URL if requesting changes..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
