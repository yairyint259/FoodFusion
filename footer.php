<?php
// footer.php
// Shared FoodFusion footer, cookie consent panel, and JavaScript.
?>

</main>

<footer class="site-footer">
    <div class="wrap footer-grid">

        <!-- FoodFusion brand -->
        <div>
            <a class="footer-brand" href="index.php">
                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                FoodFusion
            </a>

            <p>
                A culinary community for sharing recipes,
                learning cooking skills, and exploring food together.
            </p>
        </div>

        <!-- Footer links -->
        <div>
            <h3>Quick Links</h3>

            <ul>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="terms.php">Terms &amp; Conditions</a></li>
                <li><a href="policy.php">Privacy Policy</a></li>
            </ul>
        </div>

        <!-- Social media -->
        <div>
            <h3>Follow Us</h3>

            <div class="social-links">
                <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <i class="fa-brands fa-facebook" aria-hidden="true"></i>
                </a>

                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                </a>

                <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                    <i class="fa-brands fa-youtube" aria-hidden="true"></i>
                </a>

                <a href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                    <i class="fa-brands fa-tiktok" aria-hidden="true"></i>
                </a>
            </div>

            <button type="button" id="share-foodfusion" class="button button-small">
                <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                Share FoodFusion
            </button>
        </div>

    </div>

    <div class="wrap footer-bottom">
        <p>
            &copy; <?= date('Y') ?> FoodFusion. All rights reserved.
        </p>

        <button type="button" id="open-cookie-settings" class="cookie-settings-link">
            Cookie Settings
        </button>
    </div>
</footer>

<!-- Shared cookie consent panel -->
<section class="cookie-consent hidden" id="cookie-consent" role="dialog" aria-modal="false"
    aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-description" hidden>
    <div class="cookie-consent-icon" aria-hidden="true">
        <i class="fa-solid fa-cookie-bite"></i>
    </div>

    <div class="cookie-consent-copy">
        <h2 id="cookie-consent-title">Your cookie choices</h2>

        <p id="cookie-consent-description">
            We use essential browser storage to keep FoodFusion working.
            You can choose whether to allow optional preferences.
            Read our <a href="policy.php">Privacy Policy</a>.
        </p>

        <p class="cookie-consent-note">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            Essential storage is always on. You can change your choice anytime.
        </p>
    </div>

    <div class="cookie-consent-actions">
        <button class="button button-small" type="button" id="accept-all-cookies">
            Accept all
        </button>

        <button class="button button-small button-outline" type="button" id="essential-cookies-only">
            Essential only
        </button>
    </div>
</section>

<script>
    (function () {
        'use strict';

        const CONSENT_KEY = 'foodfusion-cookie-consent-v1';

        function initializeFooter() {
            const banner = document.getElementById('cookie-consent');
            const acceptButton = document.getElementById('accept-all-cookies');
            const essentialButton = document.getElementById('essential-cookies-only');
            const settingsButton = document.getElementById('open-cookie-settings');
            const shareButton = document.getElementById('share-foodfusion');

            // Cookie consent panel
            if (banner && acceptButton && essentialButton) {

                function showBanner() {
                    banner.hidden = false;
                    banner.classList.remove('hidden');
                }

                function hideBanner() {
                    banner.hidden = true;
                    banner.classList.add('hidden');
                }

                function saveConsent(choice) {
                    const consent = {
                        essential: true,
                        optional: choice === 'all',
                        choice: choice,
                        savedAt: new Date().toISOString()
                    };

                    try {
                        localStorage.setItem(
                            CONSENT_KEY,
                            JSON.stringify(consent)
                        );

                        hideBanner();

                        document.dispatchEvent(
                            new CustomEvent('foodfusion:consent-updated', {
                                detail: consent
                            })
                        );
                    } catch (error) {
                        console.error(
                            'Could not save cookie consent:',
                            error
                        );

                        // Keep the panel visible if saving fails.
                        showBanner();
                    }
                }

                acceptButton.addEventListener('click', function () {
                    saveConsent('all');
                });

                essentialButton.addEventListener('click', function () {
                    saveConsent('essential');
                });

                if (settingsButton) {
                    settingsButton.addEventListener('click', function () {
                        showBanner();
                    });
                }

                // Check whether the visitor has already made a choice.
                try {
                    const saved = localStorage.getItem(CONSENT_KEY);
                    const consent = saved ? JSON.parse(saved) : null;

                    if (
                        consent &&
                        consent.essential === true &&
                        (
                            consent.choice === 'all' ||
                            consent.choice === 'essential'
                        )
                    ) {
                        hideBanner();
                    } else {
                        showBanner();
                    }
                } catch (error) {
                    console.error(
                        'Could not read cookie consent:',
                        error
                    );

                    showBanner();
                }
            }

            // Share FoodFusion
            if (shareButton) {
                shareButton.addEventListener('click', async function () {
                    const shareData = {
                        title: 'FoodFusion',
                        text: 'Discover recipes and share your cooking ideas on FoodFusion.',
                        url: window.location.origin +
                            window.location.pathname
                    };

                    try {
                        if (navigator.share) {
                            await navigator.share(shareData);

                        } else if (
                            navigator.clipboard &&
                            window.isSecureContext
                        ) {
                            await navigator.clipboard.writeText(
                                shareData.url
                            );

                            alert('FoodFusion link copied!');

                        } else {
                            window.prompt(
                                'Copy this FoodFusion link:',
                                shareData.url
                            );
                        }
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            console.error('Sharing failed:', error);
                        }
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener(
                'DOMContentLoaded',
                initializeFooter
            );
        } else {
            initializeFooter();
        }
    })();
</script>

</body>

</html>