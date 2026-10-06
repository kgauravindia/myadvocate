<?php
// 404.php - Modern 404 Not Found Page
require_once __DIR__ . '/config/app.php';

http_response_code(404);
$pageTitle = "Page Not Found (404) - My Advocate";
$pageDescription = "The requested page on My Advocate could not be found or may have been relocated.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding: 5rem 1.25rem; text-align: center;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="font-size: 5rem; font-weight: 900; color: var(--brand-red); line-height: 1; font-family: var(--font-heading);">
            404
        </div>
        <h1 style="font-size: 2rem; color: var(--primary); margin: 1rem 0 0.5rem;">
            Page Not Found
        </h1>
        <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 2rem;">
            We couldn't find the page or statutory resource you were looking for. The link may be outdated, or the page may have been restructured.
        </p>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <a href="./" class="btn btn-primary">
                <i class="fas fa-house"></i> Return to Homepage
            </a>
            <a href="advocate-search-result" class="btn btn-outline-primary">
                <i class="fas fa-magnifying-glass"></i> Search Advocates
            </a>
            <a href="acts" class="btn btn-outline">
                <i class="fas fa-book-bookmark"></i> Bare Acts Library
            </a>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
