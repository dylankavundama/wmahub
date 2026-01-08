<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WMA Hub - Plateforme de distribution musicale pour artistes et labels">
    <title>WMAHUB - La Plateforme de Distribution musicale</title>
    <link rel="icon" type="image/png" href="asset/icon.png">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ff6600',
                        'primary-dark': '#e65c00',
                    },
                    fontFamily: {
                        sans: ['Montserrat', 'sans-serif'],
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out',
                        'fade-in-down': 'fadeInDown 0.6s ease-out',
                        'slide-in-left': 'slideInLeft 0.6s ease-out',
                        'slide-in-right': 'slideInRight 0.6s ease-out',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-50px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(50px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                    },
                },
            },
        }
    </script>
    
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8882238368661853"
     crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/actu.css">
    <link rel="stylesheet" href="css/theme-harmony.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

</head>

<body class="bg-gray-50 transition-colors duration-300">
    <!-- <div id="preloader" aria-live="polite" aria-label="Page en cours de chargement">
        <div class="preloader-content">
            <img src="asset/trans.png" alt="WMA Hub Logo" class="loader-logo">
            <div class="loader-text">Chargement...</div>
        </div>
    </div> -->

    <header class="hero-modern" role="banner" id="accueil">
        <!-- Navbar -->
        <nav class="navbar-modern" aria-label="Navigation principale">
            <div class="container">
                <a href="#accueil" class="logo-link" aria-label="Retour à l'accueil">
                    <img src="asset/trans.png" alt="WMA Logo" class="logo" loading="eager">
                </a>
                
                <!-- Menu Desktop -->
                <ul class="nav-menu" id="navMenu">
                    <li><a href="#accueil" class="nav-link active">Accueil</a></li>
                    <li><a href="#qui-sommes-nous" class="nav-link">Qui sommes-nous</a></li>
                    <!-- <li><a href="#notre-equipe" class="nav-link">Notre équipe</a></li> -->
                    <li><a href="#distribution" class="nav-link">Distribution</a></li>
                    <li><a href="#actualites" class="nav-link">Actualités</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?= $_SESSION['role'] === 'artiste' ? 'dashboards/artiste/index.php' : 'dashboards/admin/index.php' ?>" class="nav-link">Mon Dashboard</a></li>
                        <li><a href="auth/logout.php" class="nav-link text-red-500">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php" class="nav-link">Connexion</a></li>
                        <li><a href="auth/login.php" class="nav-link font-bold text-orange-500">Distribuer</a></li>
                    <?php endif; ?>
                </ul>
                
                <!-- Menu Hamburger Mobile -->
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>

        <!-- Hero Slider - Split Layout -->
        <div class="hero-slider-wrapper">
            <!-- Slides Container -->
            <div class="hero-slides-container">
                <!-- Slide 1 -->
                <div class="hero-slide active">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/aspi.jpg" alt="WMA Hub - Distribution Musicale" class="hero-portrait" loading="eager">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="eager">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    LA MUSIQUE EST UNE FORCE.<br>
                                    AMPLIFIEZ SON IMPACT.
                                </h1>
                                <p class="hero-subtitle">
                                    WMA HUB EST UNE PLATEFORME DE DISTRIBUTION MUSICALE MODERNE ET VISIONNAIRE.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn">
                                    Rejoignez WMA Hub
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>
                                <!-- Slide 5 -->
                <div class="hero-slide">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/neski.jpg" alt="Neski - Artiste WMA Hub" class="hero-portrait" loading="lazy">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="lazy">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    VOTRE MUSIQUE<br>
                                    VOTRE SUCCÈS.
                                </h1>
                                <p class="hero-subtitle">
                                    DISTRIBUEZ VOTRE MUSIQUE ET GÉNÉREZ DES REVENUS AVEC WMA HUB, VOTRE PARTENAIRE DE CONFIANCE.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn5">
                                    Commencer maintenant
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>
               <!-- Slide 4 -->
                <div class="hero-slide">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/artiste/ss.png" alt="Artiste WMA Hub" class="hero-portrait" loading="lazy">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="lazy">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    +720 ARTISTES<br>
                                    NOUS FONT CONFIANCE.
                                </h1>
                                <p class="hero-subtitle">
                                    REJOIGNEZ UNE COMMUNAUTÉ D'ARTISTES ET DE LABELS QUI TRANSFORMENT L'INDUSTRIE MUSICALE.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn4">
                                    Rejoindre la communauté
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>
                <!-- Slide 2 -->
                <div class="hero-slide">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/artiste/41163.png" alt="Artiste WMA Hub" class="hero-portrait" loading="lazy">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="lazy">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    DISTRIBUEZ VOTRE MUSIQUE<br>
                                    SUR PLUS DE 200 PLATEFORMES.
                                </h1>
                                <p class="hero-subtitle">
                                    ACCÉDEZ AUX PRINCIPALES PLATEFORMES DE STREAMING MONDIALES EN UNE SEULE FOIS.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn2">
                                    Distribuer ma musique
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="hero-slide">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/artiste/rr.png" alt="Artiste WMA Hub" class="hero-portrait" loading="lazy">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="lazy">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    ROYALTIES RAPIDES<br>
                                    SOUS 48 HEURES.
                                </h1>
                                <p class="hero-subtitle">
                                    RECEVEZ VOS REVENUS MUSICAUX RAPIDEMENT SANS FRAIS CACHÉS NI ABONNEMENT.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn3">
                                    En savoir plus
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>

 



                <!-- Slide 6 -->
                <div class="hero-slide">
                    <div class="hero-split-container">
                        <div class="hero-image-section">
                            <div class="hero-image-wrapper">
                                <img src="asset/barr.jpg" alt="Barr - Artiste WMA Hub" class="hero-portrait" loading="lazy">
                                <div class="hero-image-overlay"></div>
                                <div class="hero-slide-logo">
                                    <img src="asset/icon.png" alt="WMA Hub Logo" class="slide-logo-img" loading="lazy">
                                </div>
                                <div class="orange-shape-left"></div>
                            </div>
                        </div>
                        <div class="hero-text-section">
                            <div class="hero-text-content">
                                <h1 class="hero-main-title">
                                    VOTRE MUSIQUE<br>
                                    VOTRE SUCCÈS.
                                </h1>
                                <p class="hero-subtitle">
                                    DISTRIBUEZ VOTRE MUSIQUE ET GÉNÉREZ DES REVENUS AVEC WMA HUB, VOTRE PARTENAIRE DE CONFIANCE.
                                </p>
                                <a href="#" class="btn-hero-modern" id="distributeBtn6">
                                    Commencer maintenant
                                </a>
                            </div>
                            <div class="orange-shape-right"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Controls -->
            <div class="hero-slider-controls">
                <button class="slider-btn slider-prev" aria-label="Slide précédent">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="slider-dots">
                    <span class="dot active" data-slide="0"></span>
                    <span class="dot" data-slide="1"></span>
                    <span class="dot" data-slide="2"></span>
                    <span class="dot" data-slide="3"></span>
                    <span class="dot" data-slide="4"></span>
                    <span class="dot" data-slide="5"></span>
                </div>
                <button class="slider-btn slider-next" aria-label="Slide suivant">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </header>

    <style>
        /* Modern Hero Layout - Split Design */
        .hero-modern {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f5f5;
            position: relative;
            overflow: hidden;
        }

        .dark .hero-modern {
            background-color: #1a1a1a;
        }

        .navbar-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 15px 0;
            background: rgba(0, 0, 0, 0.85);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-modern.scrolled {
            background: rgba(0, 0, 0, 0.95);
            padding: 12px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .navbar-modern .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .logo-link {
            display: flex;
            align-items: center;
            z-index: 1001;
        }

        .logo {
            height: 60px;
            width: auto;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 2px 8px rgba(255, 102, 0, 0.3));
        }

        .logo:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 4px 12px rgba(255, 102, 0, 0.5));
        }

        .navbar-modern.scrolled .logo {
            height: 50px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 25px;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(255, 102, 0, 0.1);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 80%;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            background: rgba(255, 102, 0, 0.2);
            border: 2px solid rgba(255, 102, 0, 0.5);
            border-radius: 8px;
            cursor: pointer;
            padding: 10px;
            gap: 5px;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(255, 102, 0, 0.3);
            border-color: rgba(255, 102, 0, 0.8);
        }

        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: #ffffff;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }

        @media (max-width: 1024px) {
            .nav-menu {
                gap: 15px;
            }

            .nav-link {
                font-size: 14px;
                padding: 8px 12px;
            }
        }

        @media (max-width: 768px) {
            .navbar-modern {
                padding: 12px 0;
            }

            .logo {
                height: 40px;
            }

            .menu-toggle {
                display: flex;
            }

            .nav-menu {
                position: fixed;
                top: 0;
                right: -100%;
                width: 85%;
                max-width: 320px;
                height: 100vh;
                background: rgba(26, 26, 26, 0.98);
                flex-direction: column;
                padding: 90px 25px 30px;
                gap: 15px;
                transition: right 0.4s cubic-bezier(0.23, 1, 0.32, 1);
                z-index: 1000;
                box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
                overflow-y: auto;
            }

            .nav-menu.active {
                right: 0;
            }

            .nav-link {
                font-size: 16px;
                padding: 15px 20px;
                width: 100%;
                text-align: left;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.05);
            }

            .nav-link:hover {
                background: rgba(255, 102, 0, 0.2);
            }

            .nav-link::after {
                left: 0;
                transform: none;
            }

            .nav-link:hover::after,
            .nav-link.active::after {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .navbar-modern {
                padding: 10px 0;
            }

            .logo {
                height: 35px;
            }

            .nav-menu {
                width: 90%;
                padding: 80px 20px 30px;
            }

            .nav-link {
                font-size: 15px;
                padding: 12px 15px;
            }
        }

        .dark-mode-toggle {
            padding: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dark .dark-mode-toggle {
            background: rgba(30, 30, 30, 0.9);
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }

        /* Hero Slider Styles */
        .hero-slider-wrapper {
            position: relative;
            width: 100%;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
            overflow: hidden;
        }
        
        @media (max-width: 968px) {
            .hero-slider-wrapper {
                min-height: 100vh;
                overflow: hidden;
            }
        }
        
        @media (max-width: 480px) {
            .hero-slider-wrapper {
                min-height: 100vh;
                overflow: hidden;
            }
        }

        .hero-slides-container {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: calc(100vh - 80px);
            overflow: hidden;
        }
        
        @media (max-width: 968px) {
            .hero-slides-container {
                min-height: 100vh;
                height: 100vh;
                overflow: hidden;
            }
        }
        
        @media (max-width: 480px) {
            .hero-slides-container {
                min-height: 100vh;
                height: 100vh;
                overflow: hidden;
            }
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
            transform: translateY(100%);
        }
        
        @media (max-width: 968px) {
            .hero-slide {
                position: absolute;
                height: auto;
                min-height: 100vh;
                max-height: none;
                transform: translateX(100%);
            }
            
            .hero-slide.active {
                position: absolute;
                transform: translateX(0);
            }
            
            .hero-slide.prev {
                transform: translateX(-100%);
            }
        }
        
        @media (max-width: 480px) {
            .hero-slide {
                position: absolute;
                height: auto;
                min-height: 100vh;
                max-height: none;
                transform: translateX(100%);
            }
            
            .hero-slide.active {
                position: absolute;
                transform: translateX(0);
            }
            
            .hero-slide.prev {
                transform: translateX(-100%);
            }
        }

        .hero-slide.active {
            opacity: 1;
            transform: translateY(0);
            z-index: 2;
        }

        .hero-slide.prev {
            transform: translateY(-100%);
            z-index: 1;
        }

        .hero-split-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: calc(100vh - 80px);
        }
        
        .hero-image-section,
        .hero-text-section {
            height: 100%;
            min-height: 100%;
        }

        @media (max-width: 968px) {
            .hero-split-container {
                grid-template-columns: 1fr;
                min-height: 100vh;
                height: auto;
            }
            
            .hero-image-section {
                min-height: 40vh;
                max-height: 40vh;
                height: 40vh;
            }
            
            .hero-text-section {
                min-height: auto;
                padding: 30px 20px;
                overflow: visible;
            }
            
            .hero-text-content {
                max-width: 100%;
                padding: 0;
                min-height: auto;
            }
            
            .hero-main-title {
                font-size: 1.5rem;
                line-height: 1.3;
                margin-bottom: 12px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                min-height: auto;
            }
            
            .hero-subtitle {
                font-size: 0.95rem;
                line-height: 1.5;
                margin-bottom: 20px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                min-height: auto;
                max-height: none;
            }
            
            .btn-hero-modern {
                padding: 14px 30px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .hero-split-container {
                min-height: 100vh;
                height: auto;
            }
            
            .hero-image-section {
                min-height: 35vh;
                max-height: 35vh;
                height: 35vh;
            }
            
            .hero-text-section {
                padding: 25px 15px;
                min-height: auto;
                overflow: visible;
            }
            
            .hero-text-content {
                width: 100%;
                min-height: auto;
            }
            
            .hero-main-title {
                font-size: 1.2rem;
                line-height: 1.3;
                margin-bottom: 10px;
                letter-spacing: 0.5px;
                min-height: auto;
            }
            
            .hero-subtitle {
                font-size: 0.85rem;
                line-height: 1.4;
                margin-bottom: 18px;
                letter-spacing: 0.3px;
                min-height: auto;
                max-height: none;
            }
            
            .btn-hero-modern {
                padding: 12px 25px;
                font-size: 0.85rem;
                width: 100%;
                text-align: center;
                display: block;
            }
        }

        /* Left Section - Image */
        .hero-image-section {
            position: relative;
            overflow: hidden;
            background: #2a2a2a;
            min-height: 100%;
            display: flex;
            align-items: stretch;
        }

        .hero-image-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-portrait {
            width: 100%;
            height: 100%;
            min-height: 100%;
            object-fit: cover;
            object-position: center;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }

        .hero-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 102, 0, 0.3) 0%, rgba(255, 102, 0, 0.1) 100%);
            z-index: 1;
        }

        .orange-shape-left {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 300px;
            height: 300px;
            background: #ff6600;
            border-radius: 50% 50% 0 0;
            transform: translate(-30%, 30%);
            z-index: 2;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .hero-slide-logo {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            backdrop-filter: none;
            animation: none;
            transition: none;
        }

        .hero-slide-logo:hover {
            transform: translate(-50%, -50%);
        }

        .slide-logo-img {
            height: 50px;
            width: auto;
            max-width: 300px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.5));
            display: block;
        }

        @media (max-width: 768px) {
            .orange-shape-left {
                width: 200px;
                height: 200px;
            }
            
            .hero-slide-logo {
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
            .slide-logo-img {
                height: 60px;
                max-width: 80px;
            }
        }

        /* Right Section - Text */
        .hero-text-section {
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
            overflow: visible;
        }

        .dark .hero-text-section {
            background: #2a2a2a;
        }

        .hero-text-content {
            max-width: 600px;
            z-index: 2;
            position: relative;
            width: 100%;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        @media (max-width: 968px) {
            .hero-text-content {
                min-height: auto;
                justify-content: flex-start;
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-main-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ff6600;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: 1px;
            text-transform: uppercase;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        @media (max-width: 968px) {
            .hero-main-title {
                min-height: auto;
                justify-content: flex-start;
            }
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #1a1a1a;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            min-height: 80px;
            max-height: 80px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        
        @media (max-width: 968px) {
            .hero-subtitle {
                min-height: auto;
                max-height: none;
                overflow: visible;
            }
        }

        .dark .hero-subtitle {
            color: #e0e0e0;
        }

        .btn-hero-modern {
            display: inline-block;
            padding: 18px 40px;
            background: #1a1a1a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .dark .btn-hero-modern {
            background: #ffffff;
            color: #1a1a1a;
        }

        .btn-hero-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background: #ff6600;
            color: #ffffff;
        }

        .orange-shape-right {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: #ff6600;
            border-radius: 50% 0 0 0;
            transform: translate(30%, 30%);
            z-index: 1;
            opacity: 0.15;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 768px) {
            .orange-shape-right {
                width: 200px;
                height: 200px;
                opacity: 0.3;
                z-index: 0;
            }
            
            .hero-text-section {
                overflow: visible;
            }
        }

        @media (max-width: 480px) {
            .slide-logo-img {
                height: 40px;
                max-width: 60px;
            }
            
            .orange-shape-right {
                width: 150px;
                height: 150px;
                opacity: 0.2;
            }
        }

        /* Slider Controls */
        .hero-slider-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .dark .hero-slider-controls {
            background: rgba(30, 30, 30, 0.9);
        }

        .slider-btn {
            background: transparent;
            border: 2px solid #ff6600;
            color: #ff6600;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .slider-btn:hover {
            background: #ff6600;
            color: #ffffff;
            transform: scale(1.1);
        }

        .slider-dots {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ccc;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #ff6600;
            width: 14px;
            height: 14px;
        }

        .dot:hover {
            background: #ff6600;
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .hero-slider-controls {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .hero-slider-controls {
                display: none;
            }
        }

        /* Scroll Animation Styles */
        .scroll-animate {
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }
        
        .scroll-animate.animate {
            opacity: 1 !important;
            transform: translateY(0) translateX(0) !important;
        }
    </style>

    <style>
        /* YouTube Placeholder Styles */
        .youtube-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            z-index: 2;
            transition: opacity 0.5s ease;
        }

        .youtube-placeholder.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #youtubeIframe.loaded {
            opacity: 1 !important;
        }
    </style>

    <main id="main-content">
        <section class="about-us bg-gray-800 transition-colors duration-300" id="qui-sommes-nous">
            <div class="container">
                <h2 class="section-title scroll-animate opacity-0 translate-y-8 text-white transition-colors duration-300">
                    <i class="fas fa-info-circle mr-3"></i>QUI SOMMES NOUS ?
                </h2>
                <p class="scroll-animate opacity-0 translate-y-8 text-gray-200 transition-colors duration-300" style="animation-delay: 0.1s;">
                    <i class="fas fa-quote-left text-primary mr-2"></i>WMA Hub est une plateforme internationale de distribution musicale qui accompagne les artistes et les labels dans leur développement. Notre mission est simple : distribuer votre musique sur plus de 200 plateformes de streaming mondiales, vous offrir une meilleure visibilité et vous permettre de générer des revenus rapidement. Nous sommes une équipe de passionnés dédiée à votre succès.<i class="fas fa-quote-right text-primary ml-2"></i>
                </p>
            </div>
        </section>

        <section class="our-team bg-gray-50 transition-colors duration-300" id="notre-equipe">
            <div class="container">
                <div class="our-team-content">
                    <div class="team-image-3d-wrapper">
                        <div class="team-image-3d-container">
                            <img src="asset/off.png" alt="WMA Office - L'équipe de WMA Hub au bureau" class="team-office-img scroll-animate opacity-0 translate-y-8" loading="lazy" style="animation-delay: 0.1s;">
                        </div>
                    </div>
                    <div class="our-team-text">
                        <!-- <h2 class="section-title scroll-animate opacity-0 translate-y-8 text-primary transition-colors duration-300">
                            <i class="fas fa-users mr-3"></i>NOTRE ÉQUIPE
                        </h2> -->
                        <p class="scroll-animate opacity-0 translate-y-8 text-gray-700 transition-colors duration-300" style="animation-delay: 0.2s;">
                            <i class="fas fa-handshake text-primary mr-2"></i>Notre équipe est composée de professionnels expérimentés dans l'industrie musicale. En rejoignant WMA Hub, vous intégrez une communauté internationale de plus de 720 artistes et 50 labels. Nous mettons notre expertise et notre réseau à votre service pour maximiser votre visibilité et développer votre carrière musicale.
                        </p>
                        
                        <h2 class="section-title text-primary scroll-animate opacity-0 translate-y-8 transition-colors duration-300" style="animation-delay: 0.3s; margin-top: 40px;">
                            <i class="fas fa-music mr-3"></i>DISTRIBUEZ VOTRE MUSIQUE
                        </h2>
                        <p class="scroll-animate opacity-0 translate-y-8 text-gray-700 transition-colors duration-300" style="animation-delay: 0.4s;">
                            <i class="fas fa-check text-primary mr-2"></i>Distribuez votre musique facilement sur plus de 200 plateformes de streaming mondiales, incluant Spotify, Apple Music, Deezer, YouTube Music et bien d'autres. Aucun abonnement requis, et recevez vos royalties dans un délai maximum de 48 heures. Un processus simple, rapide et transparent.
                        </p>
                    </div>
                </div>
            </div>
        </section>


 
        <section class="sell-music-platforms bg-gray-50 transition-colors duration-300">
            <div class="container">
                <div class="platform-logos scroll-animate opacity-0 translate-y-8">
                    <img src="asset/logo/spoti.png" alt="Spotify Logo" loading="lazy" class="transition-opacity duration-300">
                    <img src="https://styleguide.audiomack.com/assets/dl/am-stacked-ffa200_000.svg"
                        alt="Audiomack Logo" loading="lazy" class="transition-opacity duration-300">
                    <img src="asset/logo/4.svg" alt="Deezer Logo" loading="lazy" class="transition-opacity duration-300">
                    <img src="asset/logo/5.svg" alt="Boomplay Logo" loading="lazy" class="transition-opacity duration-300">
                    <img src="asset/logo/3.png" alt="Apple Music Logo" loading="lazy" class="transition-opacity duration-300">
                </div>
                <h2 class="scroll-animate opacity-0 translate-y-8 text-white transition-colors duration-300">VENDEZ VOTRE MUSIQUE SUR PLUS DE 200 PLATEFORMES MONDIALES</h2>
                <div class="features-grid">
                    <div class="feature-card scroll-animate opacity-0 translate-y-8" style="animation-delay: 0.1s;">
                        <i class="fas fa-plus-circle text-4xl mb-4"></i>
                        <h3>Créer une sortie</h3>
                        <p>Commencez avec une seule piste, un EP ou un album complet. Ajoutez des collaborateurs pour
                            créditer facilement leur travail en une seule fois.</p>
                    </div>
                    <div class="feature-card scroll-animate opacity-0 translate-y-8" style="animation-delay: 0.2s;">
                        <i class="fas fa-tags text-4xl mb-4"></i>
                        <h3>Ajouter des Métadonnées</h3>
                        <p>Ajoutez votre pochette d'album, les paroles et les données des collaborateurs.</p>
                    </div>
                    <div class="feature-card scroll-animate opacity-0 translate-y-8" style="animation-delay: 0.3s;">
                        <i class="fas fa-paper-plane text-4xl mb-4"></i>
                        <h3>Distribuez votre musique</h3>
                        <p>Lancez votre musique sur tous les principaux services de musique, sociaux et vidéo. Lancez
                            une
                            campagne de pré-enregistrement et créez du buzz.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="analytics-section bg-white transition-colors duration-300">
            <div class="container">
                <div class="analytics-content">
                    <div class="analytics-text scroll-animate opacity-0 translate-x-[-50px]">
                        <h2 class="section-title text-gray-900 transition-colors duration-300">
                            <i class="fas fa-chart-line text-primary mr-2"></i>Analyses intelligentes
                        </h2>
                        <p class="text-gray-700 transition-colors duration-300">Visualisez toutes vos données de streaming de tous les DSP en un seul endroit.</p>
                        <ul class="text-gray-700 transition-colors duration-300">
                            <li><i class="fas fa-chart-line text-primary mr-2"></i> Visualisez vos données</li>
                            <li><i class="fas fa-building text-primary mr-2"></i> Comprendre votre entreprise</li>
                            <li><i class="fas fa-bullhorn text-primary mr-2"></i> Prenez de meilleures décisions marketing</li>
                            <li><i class="fas fa-lightbulb text-primary mr-2"></i> Découvrez des opportunités cachées</li>
                        </ul>
                        <a href="#" class="btn btn-primary" id="analyticsBtn">
                            <i class="fas fa-rocket mr-2"></i>Distribuer ma musique
                        </a>
                    </div>
                    <div class="analytics-chart scroll-animate opacity-0 translate-x-[50px]">
                        <div class="chart-container">
                            <h3 class="chart-title">Meilleures chaînes</h3>
                            <div class="donut-chart-wrapper">
                                <svg class="donut-chart" viewBox="0 0 200 200">
                                    <circle class="donut-ring" cx="100" cy="100" r="80" fill="none" stroke="#e0e0e0" stroke-width="30"/>
                                    <circle class="donut-segment" data-percent="34" data-color="#8B5CF6" cx="100" cy="100" r="80" fill="none" stroke="#8B5CF6" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="27" data-color="#10B981" cx="100" cy="100" r="80" fill="none" stroke="#10B981" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="25" data-color="#3B82F6" cx="100" cy="100" r="80" fill="none" stroke="#3B82F6" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="24" data-color="#EC4899" cx="100" cy="100" r="80" fill="none" stroke="#EC4899" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="28" data-color="#FF6600" cx="100" cy="100" r="80" fill="none" stroke="#FF6600" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="10" data-color="#F59E0B" cx="100" cy="100" r="80" fill="none" stroke="#F59E0B" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="16" data-color="#06B6D4" cx="100" cy="100" r="80" fill="none" stroke="#06B6D4" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                    <circle class="donut-segment" data-percent="64" data-color="#1E40AF" cx="100" cy="100" r="80" fill="none" stroke="#1E40AF" stroke-width="30" stroke-dasharray="0 502.65" stroke-dashoffset="0" transform="rotate(-90 100 100)"/>
                                </svg>
                                <div class="chart-center">
                                    <div class="chart-total">
                                        <span class="total-number" data-target="100">0</span>
                                        <span class="total-label">%</span>
                                    </div>
                                    <p class="chart-subtitle">Distribution totale</p>
                                </div>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item" data-platform="Spotify">
                                    <span class="legend-color" style="background: #10B981;"></span>
                                    <span class="legend-label">Spotify</span>
                                    <span class="legend-value" data-target="27">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Apple Music">
                                    <span class="legend-color" style="background: #1E40AF;"></span>
                                    <span class="legend-label">Apple Music</span>
                                    <span class="legend-value" data-target="64">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Boomplay">
                                    <span class="legend-color" style="background: #8B5CF6;"></span>
                                    <span class="legend-label">Boomplay</span>
                                    <span class="legend-value" data-target="34">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Amazon Music">
                                    <span class="legend-color" style="background: #FF6600;"></span>
                                    <span class="legend-label">Amazon Music</span>
                                    <span class="legend-value" data-target="28">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Deezer">
                                    <span class="legend-color" style="background: #3B82F6;"></span>
                                    <span class="legend-label">Deezer</span>
                                    <span class="legend-value" data-target="25">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="YouTube Music">
                                    <span class="legend-color" style="background: #EC4899;"></span>
                                    <span class="legend-label">YouTube Music</span>
                                    <span class="legend-value" data-target="24">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Audiomack">
                                    <span class="legend-color" style="background: #06B6D4;"></span>
                                    <span class="legend-label">Audiomack</span>
                                    <span class="legend-value" data-target="16">0</span><span>%</span>
                                </div>
                                <div class="legend-item" data-platform="Autres">
                                    <span class="legend-color" style="background: #F59E0B;"></span>
                                    <span class="legend-label">Autres</span>
                                    <span class="legend-value" data-target="10">0</span><span>%</span>
                                </div>
                            </div>
                        </div>
                        <div class="stats-cards">
                            <div class="stat-card animated-stat">
                                <div class="stat-icon"><i class="fas fa-headphones"></i></div>
                                <div class="stat-info">
                                    <span class="stat-number" data-target="80">0</span>
                                    <span class="stat-unit">M</span>
                                    <p class="stat-label">Écoutes mensuelles</p>
                                </div>
                            </div>
                            <div class="stat-card animated-stat">
                                <div class="stat-icon"><i class="fas fa-music"></i></div>
                                <div class="stat-info">
                                    <span class="stat-number" data-target="100">0</span>
                                    <span class="stat-unit">K</span>
                                    <p class="stat-label">Titres distribués</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="catalog-growth bg-gray-50">
            <div class="container">
                <div class="catalog-growth-grid">
                    <div class="catalog-column scroll-animate opacity-0 translate-y-8 bg-white transition-all duration-300" style="animation-delay: 0.1s;">
                        <h2 class="section-title text-gray-900 transition-colors duration-300"><i class="fas fa-folder-open text-primary mr-2"></i>Récupérez votre catalogue</h2>
                        <p class="text-gray-700 transition-colors duration-300">Gérez votre musique, droits d'auteur, répartitions et données tout dans un seul système.</p>
                        <ul class="text-gray-700 transition-colors duration-300">
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Gérez vos actifs</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Importez ou exportez facilement votre catalogue</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Gérez toutes vos métadonnées et actifs numériques</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Livrez des métadonnées parfaites</li>
                        </ul>
                        <a href="#" class="btn btn-primary" id="catalogBtn"><i class="fas fa-upload mr-2"></i>Distribuer ma musique</a>
                    </div>
                    <div class="growth-column scroll-animate opacity-0 translate-y-8 bg-white transition-all duration-300" style="animation-delay: 0.2s;">
                        <h2 class="section-title text-gray-900 transition-colors duration-300"><i class="fas fa-chart-bar text-primary mr-2"></i>Analysez votre croissance</h2>
                        <p class="text-gray-700 transition-colors duration-300">Obtenez toutes vos métriques de revenus et de streaming quotidiennement.</p>
                        <ul class="text-gray-700 transition-colors duration-300">
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Simplifiez le reporting</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Consolidez tous vos rapports de revenus</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Analysez facilement les données financières</li>
                            <li><i class="fas fa-check-circle text-primary mr-2"></i> Accélérez les flux de travail de reporting</li>
                        </ul>
                        <a href="#" class="btn btn-primary" id="growthBtn"><i class="fas fa-chart-line mr-2"></i>Distribuer ma musique</a>
                    </div>
                </div>
            </div>
        </section>
        <!--<section class="news-section">-->
        <!--    <div class="container">-->
        <!--        <h2 class="section-title orange-text">Actualités</h2>-->
        <!--        <div id="actualites-container" class="news-grid">-->
        <!--            </div>-->
        <!--    </div>-->
        <!--</section>-->

        <section class="news-section bg-white transition-colors duration-300" id="actualites">
                <div class="container">
                    <h2 class="section-title text-primary scroll-animate opacity-0 translate-y-8 transition-colors duration-300">
                        <i class="fas fa-newspaper mr-3"></i>Actualités
                    </h2>
                    <div id="actualites-container" class="news-grid">
                        </div>
                </div>
        </section>

        
        <section class="key-figures bg-gray-50 transition-colors duration-300">
            <div class="container">
                <div class="impact-statement scroll-animate opacity-0 translate-y-8 transition-all duration-300">
                    <p class="transition-colors duration-300">NOUS SOMMES L'UN DES ACTEURS MAJEURS DE LA DISTRIBUTION EN AFRIQUE AVEC PLUS DE 700 ARTISTES ET
                        50 LABELS EN AFRIQUE</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-globe text-primary text-5xl mb-4"></i>
                        <h3>+200 plateformes</h3>
                        <p>de distribution</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-users text-primary text-5xl mb-4"></i>
                        <h3>+720 artistes</h3>
                        <p>distribués par WMA</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-headset text-primary text-5xl mb-4"></i>
                        <h3>Assistance 24h/24 et 7j/7</h3>
                        <p>personnalisé à votre écoute</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-music text-primary text-5xl mb-4"></i>
                        <h3>+100K titres</h3>
                        <p>distribués dans le monde</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-stream text-primary text-5xl mb-4"></i>
                        <h3>+80M d'écoutes</h3>
                        <p>chaque mois grâce à ONERPM</p>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-dollar-sign text-primary text-5xl mb-4"></i>
                        <h3>48 heures</h3>
                        <p>au max pour recevoir vos royalties</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="global-presence bg-white transition-colors duration-300">
            <div class="stars-background"></div>
            <div class="container">
                <h2 class="section-title scroll-animate opacity-0 translate-y-8 text-gray-900 transition-colors duration-300">
                    <i class="fas fa-globe-americas text-primary mr-3"></i>Notre Présence Mondiale
                </h2>
                <div class="globe-3d-container scroll-animate opacity-0 translate-y-8" style="animation-delay: 0.2s;">
                    <div class="globe-wrapper">
                        <div class="globe-3d" id="globe3d">
                            <!-- Points de présence sur le globe -->
                            <div class="globe-point" data-location="North America" style="--lat: 40; --lng: -100;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">États-Unis</div>
                            </div>
                            <div class="globe-point" data-location="South America" style="--lat: -15; --lng: -50;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Brésil</div>
                            </div>
                            <div class="globe-point" data-location="Europe West" style="--lat: 50; --lng: 5;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Europe</div>
                            </div>
                            <div class="globe-point" data-location="Europe East" style="--lat: 45; --lng: 25;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Europe</div>
                            </div>
                            <div class="globe-point" data-location="Africa West" style="--lat: 8; --lng: -5;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Afrique de l'Ouest</div>
                            </div>
                            <div class="globe-point" data-location="Africa Central" style="--lat: 0; --lng: 20;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Afrique Centrale</div>
                            </div>
                            <div class="globe-point" data-location="Africa East" style="--lat: 5; --lng: 38;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Afrique de l'Est</div>
                            </div>
                            <div class="globe-point" data-location="Africa South" style="--lat: -25; --lng: 28;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Afrique du Sud</div>
                            </div>
                            <div class="globe-point" data-location="Africa North" style="--lat: 30; --lng: 10;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Afrique du Nord</div>
                            </div>
                            <div class="globe-point" data-location="Middle East" style="--lat: 30; --lng: 50;">
                                <div class="point-marker"></div>
                                <div class="point-pulse"></div>
                                <div class="point-tooltip">Moyen-Orient</div>
                            </div>
                        </div>
                    </div>
                    <div class="presence-stats">
                        <div class="presence-stat-card">
                            <div class="stat-icon-3d"><i class="fas fa-globe"></i></div>
                            <div class="stat-content">
                                <h3 class="stat-number-3d" data-target="11">0</h3>
                                <p class="stat-label-3d">Régions couvertes</p>
                            </div>
                        </div>
                        <div class="presence-stat-card">
                            <div class="stat-icon-3d"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="stat-content">
                                <h3 class="stat-number-3d" data-target="50">0</h3>
                                <p class="stat-label-3d">Pays desservis</p>
                            </div>
                        </div>
                        <div class="presence-stat-card">
                            <div class="stat-icon-3d"><i class="fas fa-users"></i></div>
                            <div class="stat-content">
                                <h3 class="stat-number-3d" data-target="720">0</h3>
                                <p class="stat-label-3d">Artistes actifs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="youtube-channel bg-gray-50 transition-colors duration-300">
            <div class="container">
                <h2 class="section-title scroll-animate opacity-0 translate-y-8 text-primary transition-colors duration-300 hidden">Notre Chaîne YouTube</h2>
                <p class="text-center text-gray-700 mb-8 scroll-animate opacity-0 translate-y-8 transition-colors duration-300" style="animation-delay: 0.1s;">
                    Découvrez nos dernières vidéos
                </p>
                <div class="youtube-embed-wrapper scroll-animate opacity-0 translate-y-8 transition-opacity duration-300" style="animation-delay: 0.2s;">
                    <div class="youtube-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                        <img src="asset/icon.png" alt="WMA Hub - Chargement de la vidéo" class="youtube-placeholder" id="youtubePlaceholder" loading="eager">
                        <iframe 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; opacity: 0; transition: opacity 0.5s ease;"
                            src="https://www.youtube.com/embed/R2a9kSeTnBs?si=IMK8_sxfPqPn2T2O" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            loading="lazy"
                            title="Chaîne YouTube WMA Hub"
                            id="youtubeIframe">
                        </iframe>
                    </div>
                </div>
                <div class="text-center mt-8 scroll-animate opacity-0 translate-y-8 transition-opacity duration-300" style="animation-delay: 0.3s;">
                    <a href="https://www.youtube.com/results?search_query=wmahub" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="btn btn-primary inline-flex items-center gap-2 hover:bg-primary-dark transition-all duration-300">
                        <i class="fab fa-youtube text-xl mr-2"></i>
                      Découvrir nos vidéos
                    </a>
                </div>
            </div>
        </section>

        
        <section class="trusted-by bg-gray-50 transition-colors duration-300">
            <div class="container">
                <div class="artist-collage-wrapper scroll-animate opacity-0 translate-y-8 transition-opacity duration-300">
                    <img src="asset/7.png" alt="Collage d'artistes qui font confiance à WMA Hub"
                        class="artist-collage-img transition-opacity duration-300" loading="lazy">
                </div>
            </div>
        </section>


        <section class="ceo-section bg-white transition-colors duration-300" id="ceo">
            <div class="container">
                <div class="ceo-content">
                    <div class="ceo-image-wrapper">
                        <div class="ceo-image-container">
                            <img src="asset/cc.jpg"CEO dMA Hub" class="ceo-image scroll-animate opacity-0 translate-y-8" loading="lazy" style="animation-delay: 0.1s;">
                            <div class="ceo-image-frame"></div>
                        </div>
                    </div>
                    <div class="ceo-text-wrapper">
                        <div class="ceo-quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="ceo-text scroll-animate opacity-0 translate-y-8 transition-colors duration-300" style="animation-delay: 0.2s;">
                            Un artiste ne peut pas mettre lui-même sa musique en ligne sur les plateformes digitales ; il doit obligatoirement passer par un distributeur spécialisé chargé d'assurer sa diffusion.
                        </p>
                        <div class="ceo-signature">
                            <p class="ceo-name">Landry Xbb</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer bg-gray-900 transition-colors duration-300" role="contentinfo">
        <div class="container">
            <p class="text-white transition-colors duration-300">&copy; Copyright 2025 WMAHUB. <br> Tous droits réservés.</p>
            <p class="text-white mt-4 text-sm transition-colors duration-300">
                Développé par 
                <a href="https://portfolio-dylan.vercel.app/" target="_blank" rel="noopener noreferrer" 
                   class="text-primary hover:text-orange-400 transition-colors duration-300 font-semibold">
                    Dylan Kavundama
                </a>
            </p>
            <div class="footer-logos">
                <img src="asset/trans.png" alt="WMA Hub Logo blanc"
                    style="height: 80px; filter: brightness(0) invert(1);">
                <img src="asset/logo/one.png" alt="ONERPM Logo" style="height: 30px;">
                <img src="asset/logo/spo.png" alt="Spotify Logo" style="height: 30px;">
                <img src="asset/logo/ub.png" alt="UB Logo" style="height: 70px;">
                <img src="asset/logo/le.png" alt="Label Engine Logo blanc"
                    style="height: 100px; filter: brightness(0) invert(1);">
                <img src="asset/logo/you.png" alt="YouTube Logo">

                <img src="asset/logo/dream.png" alt="Dream Logo" style="height: 50px;">
            </div>
            <div class="social-icons">
                <a href="https://www.instagram.com/wmahub?igsh=YW5tcjQxbWZ5NW1y" target="_blank"
                    aria-label="Suivez-nous sur Instagram" rel="noopener noreferrer"
                    class="social-icon-link">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://wa.me/243975278131" target="_blank" aria-label="Contactez-nous sur WhatsApp"
                    rel="noopener noreferrer"
                    class="social-icon-link">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="https://www.facebook.com/share/173a8UbLZf/" target="_blank"
                    aria-label="Suivez-nous sur Facebook" rel="noopener noreferrer"
                    class="social-icon-link">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://metal-server.nitrowebhost.co.uk:2096/webmaillogout.cgi" target="_blank"
                    aria-label="Contactez-nous sur Telegram" rel="noopener noreferrer"
                    class="social-icon-link">
                    <i class="fab fa-telegram"></i>
                </a>
            </div>
        </div>
    </footer>

    <div id="whatsappDialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title"
        aria-describedby="dialog-description">
        <div class="dialog-content">
            <h3 id="dialog-title">Informations de contact</h3>
            <p id="dialog-description">Veuillez remplir ces champs pour nous contacter via WhatsApp.</p>
            <label for="userName">Nom :</label>
            <input type="text" id="userName" placeholder="Votre nom complet">
            <label for="userAddress">Adresse :</label>
            <input type="text" id="userAddress" placeholder="Votre adresse email ou téléphone">
            <div class="dialog-buttons">
                <button id="sendBtn" class="btn btn-whatsapp">Envoyer sur WhatsApp</button>
                <button id="cancelBtn" class="btn btn-cancel">Annuler</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/actu.js"></script>
    <script>
        // ============================================
        // HERO SLIDER FUNCTIONALITY
        // ============================================
        (function() {
            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.dot');
            const prevBtn = document.querySelector('.slider-prev');
            const nextBtn = document.querySelector('.slider-next');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                // Remove active class from all slides and dots
                slides.forEach((slide, i) => {
                    slide.classList.remove('active', 'prev');
                    if (i < index) {
                        slide.classList.add('prev');
                    }
                });
                dots.forEach(dot => dot.classList.remove('active'));

                // Add active class to current slide and dot
                if (slides[index]) {
                    slides[index].classList.add('active');
                }
                if (dots[index]) {
                    dots[index].classList.add('active');
                }
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            function prevSlide() {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            }

            function goToSlide(index) {
                currentSlide = index;
                showSlide(currentSlide);
                resetInterval();
            }

            function startInterval() {
                slideInterval = setInterval(nextSlide, 5000); // Change every 5 seconds
            }

            function resetInterval() {
                clearInterval(slideInterval);
                startInterval();
            }

            // Event listeners
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    nextSlide();
                    resetInterval();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prevSlide();
                    resetInterval();
                });
            }

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => goToSlide(index));
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    prevSlide();
                    resetInterval();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                    resetInterval();
                }
            });

            // Pause on hover
            const sliderWrapper = document.querySelector('.hero-slider-wrapper');
            if (sliderWrapper) {
                sliderWrapper.addEventListener('mouseenter', () => {
                    clearInterval(slideInterval);
                });
                sliderWrapper.addEventListener('mouseleave', () => {
                    startInterval();
                });
            }

            // Initialize
            if (slides.length > 0) {
                showSlide(0);
                startInterval();
            }

            // Link all distribute buttons
            const distributeButtons = document.querySelectorAll('[id^="distributeBtn"]');
            const formPageUrl = 'projet.html';
            distributeButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = formPageUrl;
                });
            });
        })();

        // ============================================

        // ============================================
        // SCROLL ANIMATIONS (Intersection Observer)
        // ============================================
        (function() {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                        // Optionnel: arrêter d'observer après animation
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observer tous les éléments avec la classe scroll-animate
            document.querySelectorAll('.scroll-animate').forEach(el => {
                observer.observe(el);
            });
        })();

        // ============================================
        // OPTIMISATION: Lazy loading des images
        // ============================================
        if ('loading' in HTMLImageElement.prototype) {
            // Le navigateur supporte le lazy loading natif
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                img.addEventListener('load', function() {
                    this.classList.add('loaded');
                });
            });
        } else {
            // Fallback pour les navigateurs qui ne supportent pas le lazy loading
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
            document.body.appendChild(script);
        }

        // ============================================
        // YOUTUBE PLACEHOLDER - Masquer l'image quand la vidéo est chargée
        // ============================================
        (function() {
            const youtubeIframe = document.getElementById('youtubeIframe');
            const youtubePlaceholder = document.getElementById('youtubePlaceholder');
            
            if (youtubeIframe && youtubePlaceholder) {
                // Détecter quand la vidéo YouTube est chargée
                youtubeIframe.addEventListener('load', function() {
                    // Attendre un peu pour s'assurer que la vidéo est bien chargée
                    setTimeout(() => {
                        youtubePlaceholder.classList.add('hidden');
                        youtubeIframe.classList.add('loaded');
                    }, 500);
                });
                
                // Fallback: masquer après un délai maximum si la vidéo ne se charge pas
                setTimeout(() => {
                    if (!youtubeIframe.classList.contains('loaded')) {
                        youtubePlaceholder.classList.add('hidden');
                        youtubeIframe.classList.add('loaded');
                    }
                }, 5000);
            }
        })();

        // ============================================
        // GESTION DES BOUTONS DE REDIRECTION
        // ============================================
        const distributeBtn = document.getElementById('distributeBtn');
        const analyticsBtn = document.getElementById('analyticsBtn');
        const catalogBtn = document.getElementById('catalogBtn');
        const growthBtn = document.getElementById('growthBtn');
        const formPageUrl = 'projet.html';

        function openFormPage() {
            window.location.href = formPageUrl;
        }

        if (distributeBtn) distributeBtn.addEventListener('click', openFormPage);
        if (analyticsBtn) analyticsBtn.addEventListener('click', openFormPage);
        if (catalogBtn) catalogBtn.addEventListener('click', openFormPage);
        if (growthBtn) growthBtn.addEventListener('click', openFormPage);

        // ============================================
        // PRELOAD CRITICAL RESOURCES
        // ============================================
        const criticalImages = [
            'asset/trans.png',
            'asset/aspi.jpg'
        ];
        
        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    </script>

    <script>
        // ============================================
        // MENU NAVIGATION
        // ============================================
        (function() {
            const menuToggle = document.getElementById('menuToggle');
            const navMenu = document.getElementById('navMenu');
            const navLinks = document.querySelectorAll('.nav-link');
            
            // Toggle menu mobile
            if (menuToggle) {
                menuToggle.addEventListener('click', () => {
                    menuToggle.classList.toggle('active');
                    navMenu.classList.toggle('active');
                    menuToggle.setAttribute('aria-expanded', 
                        menuToggle.classList.contains('active') ? 'true' : 'false'
                    );
                });
            }
            
            // Fermer le menu au clic sur un lien
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        menuToggle.classList.remove('active');
                        navMenu.classList.remove('active');
                        menuToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
            
            // Fermer le menu au clic en dehors
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && 
                    navMenu.classList.contains('active') &&
                    !navMenu.contains(e.target) &&
                    !menuToggle.contains(e.target)) {
                    menuToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            });
            
            // Smooth scroll pour les liens d'ancrage
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        e.preventDefault();
                        const targetId = href.substring(1);
                        const targetElement = document.getElementById(targetId);
                        
                        if (targetElement) {
                            const headerOffset = 80;
                            const elementPosition = targetElement.getBoundingClientRect().top;
                            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                            
                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
            
            // Mise à jour du lien actif au scroll
            const sections = document.querySelectorAll('section[id]');
            
            function updateActiveLink() {
                const scrollY = window.pageYOffset;
                
                sections.forEach(section => {
                    const sectionHeight = section.offsetHeight;
                    const sectionTop = section.offsetTop - 100;
                    const sectionId = section.getAttribute('id');
                    
                    if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                        navLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${sectionId}`) {
                                link.classList.add('active');
                            }
                        });
                    }
                });
                
                // Gérer le lien "Accueil"
                if (scrollY < 100) {
                    navLinks.forEach(link => {
                        if (link.getAttribute('href') === '#accueil') {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    });
                }
            }
            
            window.addEventListener('scroll', updateActiveLink);
            updateActiveLink();
        })();
    </script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-RBQ4K1KSYF"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-RBQ4K1KSYF');
    </script>

<div id="articleDialog" class="article-dialog" aria-modal="true" role="dialog" aria-hidden="true">
    <div class="article-dialog-content">
        <button class="close-dialog-btn" aria-label="Fermer l'article">
            <i class="fas fa-times"></i>
        </button>
        <div class="article-dialog-body">
            <div class="loading-spinner"></div>
        </div>
    </div>
</div>
</body>

</html>