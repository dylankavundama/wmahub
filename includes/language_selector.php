<?php
/**
 * WMA HUB - Language Selector Component
 * Implements Google Translate with a custom premium UI.
 */
$base_path_lang = (strpos($_SERVER['REQUEST_URI'], '/wmahub') === 0) ? '/wmahub/' : '/';
?>
<!-- Google Translate Widget (Hidden) -->
<div id="google_translate_element" style="display:none"></div>

<style>
    /* Language Selector Modal Styles */
    #wma-lang-modal {
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(5, 5, 7, 0.85);
        backdrop-filter: blur(15px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #wma-lang-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .lang-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 2.5rem;
        padding: 3.5rem;
        width: 100%;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    #wma-lang-modal.show .lang-card {
        transform: translateY(0);
    }

    .lang-logo {
        height: 60px;
        margin-bottom: 2rem;
        filter: drop-shadow(0 0 20px rgba(255, 102, 0, 0.3));
    }

    .lang-title {
        font-size: 1.75rem;
        font-weight: 900;
        letter-spacing: -1px;
        margin-bottom: 1rem;
        background: linear-gradient(to right, #fff, rgba(255, 255, 255, 0.5));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .lang-options {
        display: grid;
        grid-template_columns: 1fr 1fr;
        gap: 1.5rem;
        margin-top: 2.5rem;
    }

    .lang-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 1.5rem;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        color: white;
    }

    .lang-btn:hover {
        background: rgba(255, 102, 0, 0.15);
        border-color: #ff6600;
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(255, 102, 0, 0.3);
    }

    .lang-flag {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .lang-name {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Hide Google Translate original bar */
    .skiptranslate, iframe.skiptranslate {
        display: none !important;
    }
    body {
        top: 0 !important;
    }
</style>

<div id="wma-lang-modal">
    <div class="lang-card">
        <img src="<?= $base_path_lang ?>asset/trans.png" alt="WMA Hub" class="lang-logo">
        <h2 class="lang-title">CHOISISSEZ VOTRE LANGUE</h2>
        <p class="text-gray-500 text-sm font-medium uppercase tracking-widest">Choose your language</p>
        
        <div class="lang-options">
            <button onclick="setLanguage('fr')" class="lang-btn">
                <img src="https://flagcdn.com/w160/fr.png" alt="Français" class="lang-flag">
                <span class="lang-name">Français</span>
            </button>
            <button onclick="setLanguage('en')" class="lang-btn">
                <img src="https://flagcdn.com/w160/gb.png" alt="English" class="lang-flag">
                <span class="lang-name">English</span>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'fr',
            includedLanguages: 'fr,en',
            autoDisplay: false
        }, 'google_translate_element');
    }

    function setLanguage(lang) {
        localStorage.setItem('wma_lang', lang);
        
        // Hide modal
        document.getElementById('wma-lang-modal').classList.remove('show');
        
        // Trigger Google Translate
        const select = document.querySelector('.goog-te-combo');
        if (select) {
            select.value = lang;
            select.dispatchEvent(new Event('change'));
        }
        
        // Reload if necessary (GT acts differently sometimes)
        // setTimeout(() => location.reload(), 500);
    }

    (function() {
        document.addEventListener('DOMContentLoaded', () => {
            const savedLang = localStorage.getItem('wma_lang');
            const modal = document.getElementById('wma-lang-modal');
            
            if (!savedLang) {
                // First visit
                setTimeout(() => {
                    modal.classList.add('show');
                }, 1000);
            } else if (savedLang === 'en') {
                // Re-apply translation if not French
                const checkInterval = setInterval(() => {
                    const select = document.querySelector('.goog-te-combo');
                    if (select) {
                        select.value = 'en';
                        select.dispatchEvent(new Event('change'));
                        clearInterval(checkInterval);
                    }
                }, 500);
                
                // Cleanup after 5s if GT doesn't load
                setTimeout(() => clearInterval(checkInterval), 5000);
            }
        });
    })();
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
