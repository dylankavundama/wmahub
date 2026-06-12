import React, { useState, useEffect, useRef } from 'react';
import {
  Music,
  Users,
  Search,
  ExternalLink,
  X,
  Disc,
  ArrowRight,
  Globe,
  Radio,
  Info,
  ChevronRight,
  Sliders,
  Mail,
  MessageCircle,
  Download,
  Play,
  Apple,
  Sun,
  Moon,
  Languages,
  Share2
} from 'lucide-react';

const TRANSLATIONS = {
  fr: {
    nav_home: "Accueil",
    nav_artists: "Artistes",
    nav_releases: "Sorties / Catalogue",
    nav_join: "Rejoindre",
    hero_tag: "Distribution Musicale Mondiale",
    hero_title_1: "Propulsez votre ",
    hero_title_span: "Musique",
    hero_title_2: " à l'échelle internationale",
    hero_subtitle: "WMA United Africa accompagne les labels et les talents indépendants africains dans la distribution, la promotion et la monétisation de leurs créations sur plus de 200 plateformes.",
    hero_btn_catalog: "Découvrir le Catalogue",
    hero_btn_artists: "Nos Artistes",
    feat_dist_title: "Distribution Globale",
    feat_dist_desc: "Diffusion automatique sur Spotify, Apple Music, YouTube Music, Deezer, TikTok, Amazon Music et bien d'autres.",
    feat_partner_title: "Partenaire Officiel",
    feat_partner_desc: "WMA United Africa collabore en direct avec ONErpm pour offrir un déploiement sécurisé, rapide et optimisé de vos morceaux.",
    feat_stats_title: "Statistiques Précises",
    feat_stats_desc: "Suivi clair des flux d'écoutes et monétisation transparente pour assurer la croissance des artistes.",
    section_artists_title: "Nos Artistes ",
    section_show_all: "Tout afficher",
    section_releases_title: "Dernières ",
    section_releases_span: "Sorties",
    release_date: "Sortie : ",
    release_unknown: "Indéterminée",
    listen_platforms: "Écouter sur les plateformes",
    listen: "Écouter",
    page_artists_title: "Artistes de ",
    page_artists_span: "Distribution",
    page_artists_desc: "Découvrez la liste complète des artistes officiels soutenus et diffusés par le réseau WMA United Africa.",
    search_artist_placeholder: "Rechercher un artiste...",
    empty_artist_title: "Aucun artiste trouvé",
    empty_artist_desc: "Aucun artiste ne correspond à votre recherche \"{query}\"",
    page_releases_title: "Catalogue des ",
    page_releases_span: "Sorties",
    page_releases_desc: "Explorez toutes les sorties (Singles, EPs et Albums) distribuées à travers le monde par WMA United Africa.",
    search_release_placeholder: "Rechercher un album, single, artiste...",
    filter_all: "Tous",
    empty_release_title: "Aucune distribution trouvée",
    empty_release_desc: "Aucun projet ne correspond à vos critères de recherche.",
    modal_no_bio: "Aucune description biographique disponible pour cet artiste.",
    modal_onerpm_btn: "Profil ONErpm officiel",
    modal_projects_title: "Projets Distribués",
    modal_empty_projects: "Aucun projet encore publié pour cet artiste.",
    modal_error: "Une erreur est survenue lors du chargement des détails.",
    app_title_1: "Gérez votre catalogue depuis votre ",
    app_title_span: "Smartphone",
    app_desc: "Téléchargez l'application WMA UA pour suivre nos actualités et nos dernières sorties, retrouver vos statistiques d'écoutes, vos revenus et gérer vos distributions en temps réel, où que vous soyez.",
    app_download_appstore: "Télécharger sur l'",
    app_download_playstore: "Disponible sur",
    app_download_apk: "Installation directe",
    app_apk: "Télécharger APK",
    waitlist_title: "Bientôt disponible",
    waitlist_desc: "Laissez votre mail pour être parmi les premières personnes qui vont installer l'application.",
    waitlist_placeholder: "Votre adresse email",
    waitlist_btn: "M'inscrire",
    waitlist_submitting: "Inscription...",
    waitlist_success: "Merci ! Vous avez été ajouté à la liste d'attente.",
    footer_desc: "Propulsez votre musique à l'échelle internationale avec notre réseau de distribution et d'accompagnement d'artistes.",
    footer_nav: "Navigation",
    footer_member_space: "Espace Membre",
    footer_contacts: "Contacts & Support",
    footer_rights: "© 2026 WMA United Africa. Tous droits réservés.",
    footer_visits: "Visites : {total} au total, {today} aujourd'hui",
    footer_dev: "Développé par ",
    footer_project_suffix: " projet",
    footer_projects_suffix: " projets",
    video_title: "Découvrez l'un de nos ",
    video_span: "Succès",
    video_subtitle: "Découvrez l'un de nos plus grands succès de distribution musicale sur les plateformes mondiales.",
    partners_title: "Nos Partenaires",
    partners_subtitle: "Nous collaborons avec des acteurs clés pour propulser votre musique à l'international.",
    blog_title: "Actualités & Blog",
    blog_subtitle: "Découvrez les derniers articles, actualités et conseils du réseau WMA United Africa.",
    blog_read_more: "Lire la suite",
    blog_empty: "Aucun article disponible pour le moment.",
    cta_distribute_badge: "Prêt à diffuser ?",
    cta_distribute_title: "Distribuez votre musique à l'échelle internationale",
    cta_distribute_desc: "Rejoignez le réseau WMA United Africa et diffusez vos singles, EPs ou albums sur Spotify, Apple Music, Deezer, TikTok et plus encore.",
    cta_distribute_btn: "Distribuer ma musique",
  },
  en: {
    nav_home: "Home",
    nav_artists: "Artists",
    nav_releases: "Releases / Catalog",
    nav_join: "Join",
    hero_tag: "Global Music Distribution",
    hero_title_1: "Propel your ",
    hero_title_span: "Music",
    hero_title_2: " internationally",
    hero_subtitle: "WMA United Africa supports African labels and independent talents in the distribution, promotion, and monetization of their creations across more than 200 platforms.",
    hero_btn_catalog: "Discover the Catalog",
    hero_btn_artists: "Our Artists",
    feat_dist_title: "Global Distribution",
    feat_dist_desc: "Automatic delivery to Spotify, Apple Music, YouTube Music, Deezer, TikTok, Amazon Music and many others.",
    feat_partner_title: "Official Partner",
    feat_partner_desc: "WMA United Africa collaborates directly with ONErpm to offer secure, fast, and optimized delivery of your tracks.",
    feat_stats_title: "Accurate Statistics",
    feat_stats_desc: "Clear tracking of streams and transparent monetization to ensure artist growth.",
    section_artists_title: "Our UA ",
    section_show_all: "Show all",
    section_releases_title: "Latest ",
    section_releases_span: "Releases",
    release_date: "Released: ",
    release_unknown: "TBD",
    listen_platforms: "Listen on platforms",
    listen: "Listen",
    page_artists_title: "Distribution ",
    page_artists_span: "Artists",
    page_artists_desc: "Discover the full list of official artists supported and broadcasted by the WMA United Africa network.",
    search_artist_placeholder: "Search for an artist...",
    empty_artist_title: "No artists found",
    empty_artist_desc: "No artist matches your search \"{query}\"",
    page_releases_title: "Releases ",
    page_releases_span: "Catalog",
    page_releases_desc: "Explore all releases (Singles, EPs, and Albums) distributed worldwide by WMA United Africa.",
    search_release_placeholder: "Search for an album, single, artist...",
    filter_all: "All",
    empty_release_title: "No distribution found",
    empty_release_desc: "No project matches your search criteria.",
    modal_no_bio: "No biographical description available for this artist.",
    modal_onerpm_btn: "Official ONErpm profile",
    modal_projects_title: "Distributed Projects",
    modal_empty_projects: "No projects published yet for this artist.",
    modal_error: "An error occurred while loading details.",
    app_title_1: "Manage your catalog from your ",
    app_title_span: "Smartphone",
    app_desc: "Download the WMA UA app to follow our news and latest releases, find your streaming stats, your earnings and manage your distributions in real-time, wherever you are.",
    app_download_appstore: "Download on the",
    app_download_playstore: "Get it on",
    app_download_apk: "Direct installation",
    app_apk: "Download APK",
    waitlist_title: "Coming soon",
    waitlist_desc: "Leave your email to be among the first people to install the application.",
    waitlist_placeholder: "Your email address",
    waitlist_btn: "Sign up",
    waitlist_submitting: "Signing up...",
    waitlist_success: "Thank you! You have been added to the waitlist.",
    footer_desc: "Propel your music internationally with our distribution and artist support network.",
    footer_nav: "Navigation",
    footer_member_space: "Member Space",
    footer_contacts: "Contact & Support",
    footer_rights: "© 2026 WMA United Africa. All rights reserved.",
    footer_visits: "Visits: {total} total, {today} today",
    footer_dev: "Developed by ",
    footer_project_suffix: " project",
    footer_projects_suffix: " projects",
    video_title: "Discover One of Our ",
    video_span: "Successes",
    video_subtitle: "Discover one of our greatest music distribution successes across global platforms.",
    partners_title: "Our Partners",
    partners_subtitle: "We collaborate with key players to propel your music internationally.",
    blog_title: "News & Blog",
    blog_subtitle: "Discover the latest articles, news, and tips from the WMA United Africa network.",
    blog_read_more: "Read more",
    blog_empty: "No articles available at the moment.",
    cta_distribute_badge: "Ready to release?",
    cta_distribute_title: "Distribute your music worldwide",
    cta_distribute_desc: "Join the WMA United Africa network and release your singles, EPs, or albums on Spotify, Apple Music, Deezer, TikTok, and more.",
    cta_distribute_btn: "Distribute my music",
  }
};

const SUCCESS_VIDEOS = [
  "ts87r5MVt2I",
  "WrmvVQMoztw",
  "P7ecavBTwE4",
  "prYxXdFdUhc"
];

const API_BASE = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
  ? 'http://localhost/wmahub/api'
  : 'https://wmahub.com/api';

export default function App() {
  const [selectedVideoId] = useState(() => {
    const randomIndex = Math.floor(Math.random() * SUCCESS_VIDEOS.length);
    return SUCCESS_VIDEOS[randomIndex];
  });

  const [theme, setTheme] = useState(() => {
    const saved = localStorage.getItem('wma-theme');
    if (saved) return saved;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });

  const [lang, setLang] = useState(() => {
    const params = new URLSearchParams(window.location.search);
    const urlLang = params.get('lang');
    if (urlLang && (urlLang === 'en' || urlLang === 'fr')) {
      localStorage.setItem('wma-lang', urlLang);
      return urlLang;
    }
    const saved = localStorage.getItem('wma-lang');
    if (saved) return saved;
    const systemLang = navigator.language || 'fr';
    return systemLang.toLowerCase().startsWith('en') ? 'en' : 'fr';
  });

  const t = (key, params = {}) => {
    let text = TRANSLATIONS[lang][key] || TRANSLATIONS['fr'][key] || key;
    Object.keys(params).forEach(p => {
      text = text.replace(`{${p}}`, params[p]);
    });
    return text;
  };

  useEffect(() => {
    document.body.className = theme === 'dark' ? 'dark-theme' : 'light-theme';
    localStorage.setItem('wma-theme', theme);
  }, [theme]);

  useEffect(() => {
    localStorage.setItem('wma-lang', lang);
  }, [lang]);

  const [showHeader, setShowHeader] = useState(true);
  const lastScrollY = useRef(0);
  const ticking = useRef(false);

  useEffect(() => {
    const updateHeader = () => {
      const currentScrollY = window.scrollY;
      if (currentScrollY > lastScrollY.current && currentScrollY > 100) {
        setShowHeader(false);
      } else {
        setShowHeader(true);
      }
      lastScrollY.current = currentScrollY;
      ticking.current = false;
    };

    const handleScroll = () => {
      if (!ticking.current) {
        window.requestAnimationFrame(updateHeader);
        ticking.current = true;
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const [activeTab, setActiveTab] = useState('home');
  const [artists, setArtists] = useState([]);
  const [projects, setProjects] = useState([]);
  const [artistsLoading, setArtistsLoading] = useState(true);
  const [projectsLoading, setProjectsLoading] = useState(true);

  // Selected Artist details modal
  const [selectedArtistId, setSelectedArtistId] = useState(null);
  const [artistDetails, setArtistDetails] = useState(null);
  const [detailsLoading, setDetailsLoading] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('All');

  // Waitlist popup state
  const [showWaitlistModal, setShowWaitlistModal] = useState(false);
  const [waitlistEmail, setWaitlistEmail] = useState('');
  const [waitlistSubmitting, setWaitlistSubmitting] = useState(false);
  const [waitlistSuccess, setWaitlistSuccess] = useState(false);
  const [waitlistError, setWaitlistError] = useState('');

  const [blogPosts, setBlogPosts] = useState([]);
  const [blogLoading, setBlogLoading] = useState(true);
  const [shareCopied, setShareCopied] = useState(false);
  const [visitStats, setVisitStats] = useState({ total: 100, today: 0 });

  const handleShare = async () => {
    if (!artistDetails || !artistDetails.artist) return;
    const shareUrl = `${window.location.origin}${window.location.pathname}?artist=${artistDetails.artist.id}`;
    const shareTitle = artistDetails.artist.name;
    const shareText = lang === 'fr' 
      ? `Découvrez le profil de ${artistDetails.artist.name} sur WMA United Africa`
      : `Check out ${artistDetails.artist.name}'s profile on WMA United Africa`;

    if (navigator.share) {
      try {
        await navigator.share({
          title: shareTitle,
          text: shareText,
          url: shareUrl
        });
      } catch (err) {
        console.error("Error sharing:", err);
      }
    } else {
      try {
        await navigator.clipboard.writeText(shareUrl);
        setShareCopied(true);
        setTimeout(() => setShareCopied(false), 3000);
      } catch (err) {
        console.error("Error copying to clipboard:", err);
      }
    }
  };

  // Check URL query parameters on mount to open specific artist modal
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const artistId = params.get('artist');
    if (artistId) {
      const parsedId = parseInt(artistId, 10);
      if (!isNaN(parsedId)) {
        setSelectedArtistId(parsedId);
      }
    }
  }, []);

  // Load all artists and projects on mount
  useEffect(() => {
    fetchArtists();
    fetchProjects();
    fetchBlogPosts();
    fetchVisitStats();
  }, []);

  const fetchBlogPosts = async () => {
    try {
      setBlogLoading(true);
      const res = await fetch('https://wmahub.com/blog/wp-json/wp/v2/posts?per_page=4&_embed');
      if (res.ok) {
        const data = await res.json();
        setBlogPosts(Array.isArray(data) ? data : []);
      }
    } catch (e) {
      console.error("Error fetching WordPress posts:", e);
    } finally {
      setBlogLoading(false);
    }
  };

  const fetchVisitStats = async () => {
    try {
      const res = await fetch(`${API_BASE}/ua_visits.php`);
      if (res.ok) {
        const data = await res.json();
        if (data && data.success) {
          setVisitStats({
            total: data.total ?? 100,
            today: data.today ?? 0
          });
        }
      }
    } catch (e) {
      console.error("Error fetching visit stats:", e);
    }
  };

  const fetchArtists = async () => {
    try {
      setArtistsLoading(true);
      const res = await fetch(`${API_BASE}/ua_get_artists.php`);
      const data = await res.json();
      setArtists(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error("Error fetching UA artists:", e);
    } finally {
      setArtistsLoading(false);
    }
  };

  const fetchProjects = async () => {
    try {
      setProjectsLoading(true);
      const res = await fetch(`${API_BASE}/ua_get_projects.php`);
      const data = await res.json();
      setProjects(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error("Error fetching UA projects:", e);
    } finally {
      setProjectsLoading(false);
    }
  };

  const fetchArtistDetails = async (id) => {
    try {
      setDetailsLoading(true);
      setArtistDetails(null);
      const res = await fetch(`${API_BASE}/ua_get_artist_details.php?id=${id}`);
      const data = await res.json();
      if (data && !data.error) {
        setArtistDetails(data);
      }
    } catch (e) {
      console.error("Error fetching artist details:", e);
    } finally {
      setDetailsLoading(false);
    }
  };

  const handleWaitlistSubmit = async (e) => {
    e.preventDefault();
    if (!waitlistEmail) return;

    try {
      setWaitlistSubmitting(true);
      setWaitlistError('');
      
      const res = await fetch(`${API_BASE}/save_waitlist_email.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email: waitlistEmail })
      });

      const data = await res.json();
      
      if (data.success) {
        setWaitlistSuccess(true);
        setWaitlistEmail('');
      } else {
        setWaitlistError(data.message || 'Une erreur est survenue.');
      }
    } catch (err) {
      console.error(err);
      setWaitlistError(lang === 'fr' ? 'Erreur de connexion avec le serveur.' : 'Connection error with the server.');
    } finally {
      setWaitlistSubmitting(false);
    }
  };

  // Trigger details fetch when artist is selected
  useEffect(() => {
    if (selectedArtistId) {
      fetchArtistDetails(selectedArtistId);
    } else {
      setArtistDetails(null);
    }
  }, [selectedArtistId]);

  // Filters
  const filteredArtists = artists.filter(art =>
    art.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const filteredProjects = projects.filter(proj => {
    const matchesSearch = proj.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      proj.ua_artist_name.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesType = filterType === 'All' || proj.type === filterType;
    return matchesSearch && matchesType;
  });

  return (
    <>
      {/* Header */}
      <header className={`header ${showHeader ? '' : 'header-hidden'}`}>
        <div className="container header-container">
          <a href="#" className="logo-link" onClick={(e) => { e.preventDefault(); setActiveTab('home'); }}>
            <span className="logo-text">WMA UNITED AFRICA</span>
          </a>
          <nav className="header-nav">
            <ul className="nav-menu">
              <li>
                <a
                  href="#home"
                  className={`nav-item-link ${activeTab === 'home' ? 'active' : ''}`}
                  onClick={(e) => { e.preventDefault(); setActiveTab('home'); }}
                >
                  {t('nav_home')}
                </a>
              </li>
              <li>
                <a
                  href="#artists"
                  className={`nav-item-link ${activeTab === 'artists' ? 'active' : ''}`}
                  onClick={(e) => { e.preventDefault(); setActiveTab('artists'); }}
                >
                  {t('nav_artists')}
                </a>
              </li>
              <li>
                <a
                  href="#distributions"
                  className={`nav-item-link ${activeTab === 'distributions' ? 'active' : ''}`}
                  onClick={(e) => { e.preventDefault(); setActiveTab('distributions'); }}
                >
                  {t('nav_releases')}
                </a>
              </li>
            </ul>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
              <button 
                className="icon-btn lang-toggle"
                onClick={() => setLang(lang === 'fr' ? 'en' : 'fr')}
                title={lang === 'fr' ? 'Switch to English' : 'Passer en Français'}
              >
                <Languages size={16} />
                <span>{lang.toUpperCase()}</span>
              </button>
              <button 
                className="icon-btn theme-toggle"
                onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                title={theme === 'dark' ? 'Mode Clair' : 'Mode Sombre'}
              >
                {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
              </button>
              <a
                href="https://wmahub.com"
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-solid btn-join"
              >
                {t('nav_join')}
              </a>
            </div>
          </nav>
        </div>
      </header>

      {/* Main Content */}
      <main style={{ flex: 1 }}>
        {activeTab === 'home' && (
          <div>
            {/* Hero */}
            <section className="hero">
              <div className="container">
                <span className="hero-tag">
                  <Radio size={14} /> {t('hero_tag')}
                </span>
                <h1 className="hero-title">
                  {t('hero_title_1')}<span>{t('hero_title_span')}</span>{t('hero_title_2')}
                </h1>
                <p className="hero-subtitle">
                  {t('hero_subtitle')}
                </p>
                <div className="hero-actions">
                  <button onClick={() => setActiveTab('distributions')} className="btn btn-solid">
                    {t('hero_btn_catalog')} <ArrowRight size={16} />
                  </button>
                  <button onClick={() => setActiveTab('artists')} className="btn btn-outline">
                    {t('hero_btn_artists')} <Users size={16} />
                  </button>
                </div>
              </div>
            </section>

            {/* Info Section */}
            <section style={{ padding: '4rem 0', background: 'rgba(255,255,255,0.01)', borderTop: '1px solid var(--border-color)', borderBottom: '1px solid var(--border-color)' }}>
              <div className="container" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '2rem' }}>
                <div className="glass-card" style={{ textAlign: 'left' }}>
                  <div style={{ color: 'var(--primary)', marginBottom: '1rem' }}><Music size={32} /></div>
                  <h3 style={{ fontSize: '1.25rem', marginBottom: '0.5rem' }}>{t('feat_dist_title')}</h3>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', lineHeight: '1.5' }}>
                    {t('feat_dist_desc')}
                  </p>
                </div>
                <div className="glass-card" style={{ textAlign: 'left' }}>
                  <div style={{ color: 'var(--primary)', marginBottom: '1rem' }}><Globe size={32} /></div>
                  <h3 style={{ fontSize: '1.25rem', marginBottom: '0.5rem' }}>{t('feat_partner_title')}</h3>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', lineHeight: '1.5' }}>
                    {t('feat_partner_desc')}
                  </p>
                </div>
                <div className="glass-card" style={{ textAlign: 'left' }}>
                  <div style={{ color: 'var(--primary)', marginBottom: '1rem' }}><Disc size={32} /></div>
                  <h3 style={{ fontSize: '1.25rem', marginBottom: '0.5rem' }}>{t('feat_stats_title')}</h3>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', lineHeight: '1.5' }}>
                    {t('feat_stats_desc')}
                  </p>
                </div>
              </div>
            </section>

            {/* Featured Artists preview */}
            <section style={{ padding: '5rem 0' }}>
              <div className="container">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '3rem' }}>
                  <h2 style={{ fontSize: '2rem' }}>{t('section_artists_title')}<span>UA</span></h2>
                  <button onClick={() => setActiveTab('artists')} className="btn btn-outline" style={{ padding: '0.5rem 1.25rem', fontSize: '0.85rem' }}>
                    {t('section_show_all')} <ChevronRight size={14} />
                  </button>
                </div>

                {artistsLoading ? (
                  <div className="spinner-wrapper"><div className="spinner"></div></div>
                ) : (
                  <div className="grid-artists">
                    {artists.slice(0, 4).map(art => (
                      <div key={art.id} className="artist-card" onClick={() => setSelectedArtistId(art.id)}>
                        <div className="artist-photo-container">
                          <img
                            src={art.photo_url || 'https://wmahub.com/asset/aspi.jpg'}
                            alt={art.name}
                            className="artist-photo"
                            onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                          />
                        </div>
                        <h3 className="artist-name">{art.name}</h3>
                        <span className="artist-project-count">{art.project_count} {art.project_count > 1 ? t('footer_projects_suffix') : t('footer_project_suffix')}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </section>

            {/* Latest Releases preview */}
            <section style={{ padding: '5rem 0', background: 'rgba(0,0,0,0.2)' }}>
              <div className="container">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '3rem' }}>
                  <h2 style={{ fontSize: '2rem' }}>{t('section_releases_title')}<span>{t('section_releases_span')}</span></h2>
                  <button onClick={() => setActiveTab('distributions')} className="btn btn-outline" style={{ padding: '0.5rem 1.25rem', fontSize: '0.85rem' }}>
                    {t('section_show_all')} <ChevronRight size={14} />
                  </button>
                </div>

                {projectsLoading ? (
                  <div className="spinner-wrapper"><div className="spinner"></div></div>
                ) : (
                  <div className="grid-projects">
                    {projects.slice(0, 4).map(proj => (
                      <div key={proj.id} className="project-card">
                        <div>
                          <div className="project-cover-container">
                            <img
                              src={proj.image_url || 'https://wmahub.com/asset/aspi.jpg'}
                              alt={proj.title}
                              className="project-cover"
                              onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                            />
                            <span className="project-badge">{proj.type}</span>
                          </div>
                          <h3 className="project-title">{proj.title}</h3>
                          <p className="project-artist">{proj.ua_artist_name}</p>
                          <div className="project-meta">
                            <span>{t('release_date')}{proj.release_date ? new Date(proj.release_date).toLocaleDateString(lang === 'fr' ? 'fr-FR' : 'en-US') : t('release_unknown')}</span>
                          </div>
                        </div>
                        <a href={proj.link} target="_blank" rel="noopener noreferrer" className="stream-btn">
                          <Radio size={14} /> {t('listen_platforms')}
                        </a>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </section>

            {/* Distribution CTA Section */}
            <section className="distribution-cta-section">
              <div className="cta-bg-images">
                <div className="cta-bg-col">
                  <img src="./bg_studio.png" alt="Studio" className="cta-bg-img" />
                  <div className="cta-bg-overlay"></div>
                </div>
                <div className="cta-bg-col">
                  <img src="./bg_concert.png" alt="Concert" className="cta-bg-img" />
                  <div className="cta-bg-overlay"></div>
                </div>
                <div className="cta-bg-col">
                  <img src="./bg_streaming.png" alt="Streaming" className="cta-bg-img" />
                  <div className="cta-bg-overlay"></div>
                </div>
              </div>
              
              <div className="cta-content">
                <span className="cta-badge">
                  <Radio size={12} /> {t('cta_distribute_badge')}
                </span>
                <h2 className="cta-title">
                  {lang === 'fr' ? <>Distribuez votre <span>Musique</span></> : <>Distribute your <span>Music</span></>}
                </h2>
                <p className="cta-desc">
                  {t('cta_distribute_desc')}
                </p>
                <div className="cta-btn-wrapper">
                  <a href="https://wmahub.com/auth/login.php" target="_blank" rel="noopener noreferrer" className="cta-btn">
                    {t('cta_distribute_btn')} <ArrowRight size={18} />
                  </a>
                </div>
              </div>
            </section>
          </div>
        )}

        {activeTab === 'artists' && (
          <section style={{ padding: '4rem 0' }}>
            <div className="container">
              <header style={{ marginBottom: '3rem', textAlign: 'center' }}>
                <h1 style={{ fontSize: '3rem', marginBottom: '1rem' }}>{t('page_artists_title')}<span>{t('page_artists_span')}</span></h1>
                <p style={{ color: 'var(--text-muted)', maxWWidth: '600px', margin: '0 auto 2.5rem' }}>
                  {t('page_artists_desc')}
                </p>

                {/* Search Bar */}
                <div style={{ display: 'flex', justifyContent: 'center' }}>
                  <div className="search-input-wrapper">
                    <Search size={18} className="search-input-icon" />
                    <input
                      type="text"
                      placeholder={t('search_artist_placeholder')}
                      className="search-input"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                    />
                  </div>
                </div>
              </header>

              {artistsLoading ? (
                <div className="spinner-wrapper"><div className="spinner"></div></div>
              ) : (
                <div className="grid-artists">
                  {filteredArtists.length === 0 ? (
                    <div className="empty-state">
                      <Users size={48} className="empty-state-icon" />
                      <h3>{t('empty_artist_title')}</h3>
                      <p style={{ marginTop: '0.5rem' }}>{t('empty_artist_desc', { query: searchQuery })}</p>
                    </div>
                  ) : (
                    filteredArtists.map(art => (
                      <div key={art.id} className="artist-card" onClick={() => setSelectedArtistId(art.id)}>
                        <div className="artist-photo-container">
                          <img
                            src={art.photo_url || 'https://wmahub.com/asset/aspi.jpg'}
                            alt={art.name}
                            className="artist-photo"
                            onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                          />
                        </div>
                        <h3 className="artist-name">{art.name}</h3>
                        <span className="artist-project-count">{art.project_count} {art.project_count > 1 ? t('footer_projects_suffix') : t('footer_project_suffix')}</span>
                      </div>
                    ))
                  )}
                </div>
              )}
            </div>
          </section>
        )}

        {activeTab === 'distributions' && (
          <section style={{ padding: '4rem 0' }}>
            <div className="container">
              <header style={{ marginBottom: '3rem', textAlign: 'center' }}>
                <h1 style={{ fontSize: '3rem', marginBottom: '1rem' }}>{t('page_releases_title')}<span>{t('page_releases_span')}</span></h1>
                <p style={{ color: 'var(--text-muted)', maxWWidth: '600px', margin: '0 auto 2.5rem' }}>
                  {t('page_releases_desc')}
                </p>

                {/* Search & Filters */}
                <div className="filter-bar">
                  <div className="search-input-wrapper">
                    <Search size={18} className="search-input-icon" />
                    <input
                      type="text"
                      placeholder={t('search_release_placeholder')}
                      className="search-input"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                    />
                  </div>

                  <div className="filter-tags">
                    {['All', 'Single', 'EP', 'Album'].map(type => (
                      <button
                        key={type}
                        className={`filter-tag ${filterType === type ? 'active' : ''}`}
                        onClick={() => setFilterType(type)}
                      >
                        {type === 'All' ? t('filter_all') : type}
                      </button>
                    ))}
                  </div>
                </div>
              </header>

              {projectsLoading ? (
                <div className="spinner-wrapper"><div className="spinner"></div></div>
              ) : (
                <div className="grid-projects">
                  {filteredProjects.length === 0 ? (
                    <div className="empty-state">
                      <Music size={48} className="empty-state-icon" />
                      <h3>{t('empty_release_title')}</h3>
                      <p style={{ marginTop: '0.5rem' }}>{t('empty_release_desc')}</p>
                    </div>
                  ) : (
                    filteredProjects.map(proj => (
                      <div key={proj.id} className="project-card">
                        <div>
                          <div className="project-cover-container">
                            <img
                              src={proj.image_url || 'https://wmahub.com/asset/aspi.jpg'}
                              alt={proj.title}
                              className="project-cover"
                              onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                            />
                            <span className="project-badge">{proj.type}</span>
                          </div>
                          <h3 className="project-title">{proj.title}</h3>
                          <p className="project-artist">{proj.ua_artist_name}</p>
                          <div className="project-meta">
                            <span>{t('release_date')}{proj.release_date ? new Date(proj.release_date).toLocaleDateString(lang === 'fr' ? 'fr-FR' : 'en-US') : t('release_unknown')}</span>
                          </div>
                        </div>
                        <a href={proj.link} target="_blank" rel="noopener noreferrer" className="stream-btn">
                          <Radio size={14} /> {t('listen_platforms')}
                        </a>
                      </div>
                    ))
                  )}
                </div>
              )}
            </div>
          </section>
        )}
      </main>

      {/* Artist Details Modal */}
      {selectedArtistId && (
        <div className="modal-overlay" onClick={() => setSelectedArtistId(null)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close-btn" onClick={() => setSelectedArtistId(null)}>
              <X size={18} />
            </button>

            {detailsLoading ? (
              <div className="spinner-wrapper" style={{ minHeight: '350px' }}><div className="spinner"></div></div>
            ) : artistDetails ? (
              <div>
                <div className="artist-detail-hero">
                  <img
                    src={artistDetails.artist.photo_url || 'https://wmahub.com/asset/aspi.jpg'}
                    alt={artistDetails.artist.name}
                    className="artist-detail-photo"
                    onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                  />
                  <div className="artist-detail-info">
                    <h2 className="artist-detail-name">{artistDetails.artist.name}</h2>
                    <p className="artist-detail-bio">
                      {artistDetails.artist.bio || t('modal_no_bio')}
                    </p>
                    {artistDetails.artist.onerpm_link && (
                      <div style={{ display: 'flex' }}>
                        <a
                          href={artistDetails.artist.onerpm_link}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="btn btn-solid"
                          style={{ padding: '0.65rem 1.25rem', fontSize: '0.85rem' }}
                        >
                          <ExternalLink size={14} /> {t('modal_onerpm_btn')}
                        </a>
                      </div>
                    )}
                  </div>
                </div>

                <div className="artist-detail-projects">
                  {artistDetails.projects.length === 0 ? (
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '3rem 1rem', gap: '1rem', background: 'var(--bg-card)', border: '1px dashed var(--border-color)', borderRadius: '1.5rem', textAlign: 'center' }}>
                      <button 
                        onClick={handleShare}
                        className="btn btn-solid"
                        style={{ padding: '0.85rem 2.25rem', display: 'inline-flex', alignItems: 'center', gap: '0.5rem', background: 'var(--primary)', border: 'none', borderRadius: '0.75rem', color: '#fff', fontWeight: 600, cursor: 'pointer' }}
                      >
                        <Share2 size={16} /> {lang === 'fr' ? 'Partager le profil' : 'Share profile'}
                      </button>
                      {shareCopied && (
                        <span style={{ fontSize: '0.85rem', color: '#10b981', fontWeight: 600 }}>
                          ✓ {lang === 'fr' ? 'Lien de partage copié !' : 'Share link copied!'}
                        </span>
                      )}
                    </div>
                  ) : (
                    <>
                      <h3 className="artist-projects-title">
                        <Disc size={20} style={{ color: 'var(--primary)' }} /> {t('modal_projects_title')}
                      </h3>
                      <div className="grid-projects">
                        {artistDetails.projects.map(proj => (
                          <div key={proj.id} className="project-card">
                            <div>
                              <div className="project-cover-container">
                                <img
                                  src={proj.image_url || 'https://wmahub.com/asset/aspi.jpg'}
                                  alt={proj.title}
                                  className="project-cover"
                                  onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                                />
                                <span className="project-badge">{proj.type}</span>
                              </div>
                              <h3 className="project-title">{proj.title}</h3>
                              <div className="project-meta" style={{ marginBottom: '1rem' }}>
                                <span>{t('release_date')}{proj.release_date ? new Date(proj.release_date).toLocaleDateString(lang === 'fr' ? 'fr-FR' : 'en-US') : t('release_unknown')}</span>
                              </div>
                            </div>
                            <a href={proj.link} target="_blank" rel="noopener noreferrer" className="stream-btn">
                              <Radio size={14} /> {t('listen')}
                            </a>
                          </div>
                        ))}
                      </div>
                    </>
                  )}
                </div>
              </div>
            ) : (
              <div className="empty-state" style={{ minHeight: '300px', display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
                <Info size={40} className="empty-state-icon" />
                <p>{t('modal_error')}</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Waitlist Modal */}
      {showWaitlistModal && (
        <div className="modal-overlay" onClick={() => { setShowWaitlistModal(false); setWaitlistSuccess(false); setWaitlistError(''); }}>
          <div className="modal-content" style={{ maxWidth: '450px' }} onClick={(e) => e.stopPropagation()}>
            <button className="modal-close-btn" onClick={() => { setShowWaitlistModal(false); setWaitlistSuccess(false); setWaitlistError(''); }}>
              <X size={18} />
            </button>
            <div style={{ textAlign: 'center', padding: '2rem 1rem' }}>
              <div style={{ color: 'var(--primary)', marginBottom: '1.5rem', display: 'flex', justifyContent: 'center' }}>
                <Download size={48} />
              </div>
              <h2 style={{ fontSize: '1.75rem', marginBottom: '1rem', color: 'var(--text-color)' }}>
                {t('waitlist_title')}
              </h2>
              
              {waitlistSuccess ? (
                <div style={{ marginTop: '1.5rem' }}>
                  <div style={{ color: '#10b981', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '0.5rem' }}>✓</div>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.95rem' }}>{t('waitlist_success')}</p>
                </div>
              ) : (
                <>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.95rem', lineHeight: '1.6', marginBottom: '2rem' }}>
                    {t('waitlist_desc')}
                  </p>
                  
                  <form onSubmit={handleWaitlistSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                    <input
                      type="email"
                      required
                      placeholder={t('waitlist_placeholder')}
                      value={waitlistEmail}
                      onChange={(e) => setWaitlistEmail(e.target.value)}
                      style={{
                        width: '100%',
                        background: 'rgba(255, 255, 255, 0.05)',
                        border: '1px solid var(--border-color)',
                        borderRadius: '0.75rem',
                        padding: '0.85rem 1rem',
                        color: 'var(--text-color)',
                        fontSize: '0.95rem',
                        outline: 'none',
                        boxSizing: 'border-box'
                      }}
                    />
                    {waitlistError && (
                      <p style={{ color: '#ef4444', fontSize: '0.85rem', textAlign: 'left', margin: '0.25rem 0' }}>
                        {waitlistError}
                      </p>
                    )}
                    <button
                      type="submit"
                      disabled={waitlistSubmitting}
                      className="btn btn-solid"
                      style={{
                        padding: '0.85rem 1.5rem',
                        fontSize: '0.95rem',
                        fontWeight: '600',
                        borderRadius: '0.75rem',
                        width: '100%',
                        justifyContent: 'center',
                        marginTop: '0.5rem',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.5rem'
                      }}
                    >
                      {waitlistSubmitting ? t('waitlist_submitting') : t('waitlist_btn')}
                    </button>
                  </form>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {/* App Download Banner */}
      <section className="app-download-section">
        <div className="container app-download-container">
          <div className="app-download-content">
            <h2 className="app-download-title">{t('app_title_1')}<span>{t('app_title_span')}</span></h2>
            <p className="app-download-subtitle">
              {t('app_desc')}
            </p>
          </div>
          <div className="app-download-buttons">
            <a href="#" className="app-btn store-btn" onClick={(e) => { e.preventDefault(); setShowWaitlistModal(true); }}>
              <span className="app-btn-icon"><Apple size={24} style={{ fill: 'currentColor' }} /></span>
              <div className="app-btn-text">
                <span className="app-btn-sub">{t('app_download_appstore')}</span>
                <span className="app-btn-title">App Store</span>
              </div>
            </a>

            <a href="#" className="app-btn store-btn" onClick={(e) => { e.preventDefault(); setShowWaitlistModal(true); }}>
              <span className="app-btn-icon"><Play size={24} style={{ fill: 'currentColor' }} /></span>
              <div className="app-btn-text">
                <span className="app-btn-sub">{t('app_download_playstore')}</span>
                <span className="app-btn-title">Google Play</span>
              </div>
            </a>
          </div>
        </div>
      </section>

      {/* Blog Section */}
      {activeTab === 'home' && (
        <section className="blog-section">
          <div className="container">
            <h2 className="blog-title">{t('blog_title')}</h2>
            <p className="blog-subtitle">{t('blog_subtitle')}</p>
            
            {blogLoading ? (
              <div className="spinner-wrapper" style={{ padding: '3rem 0' }}><div className="spinner"></div></div>
            ) : blogPosts.length === 0 ? (
              <div className="empty-state" style={{ padding: '3rem' }}>
                <p>{t('blog_empty')}</p>
              </div>
            ) : (
              <div className="blog-grid">
                {blogPosts.map(post => {
                  const featuredImage = post._embedded?.['wp:featuredmedia']?.[0]?.source_url || 
                                       post.jetpack_featured_media_url || 
                                       'https://wmahub.com/asset/aspi.jpg';
                  const excerpt = (post.excerpt?.rendered || '')
                    .replace(/<p>|<\/p>|<a\b[^>]*>(.*?)<\/a>|&hellip;/g, '')
                    .replace(/&#8217;/g, "'")
                    .replace(/&#8230;/g, "...")
                    .replace(/&#8211;/g, "-")
                    .substring(0, 120) + '...';
                  
                  const postDate = new Date(post.date).toLocaleDateString(lang === 'fr' ? 'fr-FR' : 'en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                  });

                  return (
                    <article key={post.id} className="blog-card">
                      <div className="blog-card-image-wrapper">
                        <img 
                          src={featuredImage} 
                          alt={post.title.rendered} 
                          className="blog-card-image"
                          onError={(e) => { e.target.src = 'https://wmahub.com/asset/aspi.jpg'; }}
                        />
                      </div>
                      <div className="blog-card-content">
                        <span className="blog-card-date">{postDate}</span>
                        <h3 className="blog-card-title" dangerouslySetInnerHTML={{ __html: post.title.rendered }}></h3>
                        <p className="blog-card-excerpt" dangerouslySetInnerHTML={{ __html: excerpt }}></p>
                        <a href={post.link} target="_blank" rel="noopener noreferrer" className="blog-card-btn">
                          {t('blog_read_more')} <ArrowRight size={14} />
                        </a>
                      </div>
                    </article>
                  );
                })}
              </div>
            )}
          </div>
        </section>
      )}

      {/* Partners Section */}
      <section className="partners-section">
        <div className="container">
          <h2 className="partners-title">{t('partners_title')}</h2>
          <p className="partners-subtitle">{t('partners_subtitle')}</p>
          <div className="partners-grid">
            <div className="partner-card" id="partner-card-onerpm">
              <a href="https://onerpm.com" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="https://upload.wikimedia.org/wikipedia/commons/8/87/ONErpm.svg" alt="Onerpm" className="partner-logo" />
              </a>
            </div>
            <div className="partner-card" id="partner-card-spotify">
              <a href="https://spotify.com" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/26/Spotify_logo_with_text.svg" alt="Spotify" className="partner-logo" />
              </a>
            </div>
            <div className="partner-card" id="partner-card-youtube">
              <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/YouTube_Logo_2017.svg" alt="YouTube" className="partner-logo" />
              </a>
            </div>
            <div className="partner-card" id="partner-card-nextbyte">
              <a href="https://nextbytechno.com/" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="./Next.png" alt="Next Byte Technology" className="partner-logo" />
              </a>
            </div>
            <div className="partner-card" id="partner-card-azana">
              <a href="https://www.azanaworldwide.online" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="https://www.azanaworldwide.online/azana-logo.png" alt="Azana" className="partner-logo" />
              </a>
            </div>
            <div className="partner-card" id="partner-card-wmaplus">
              <a href="https://wmaplus.com" target="_blank" rel="noopener noreferrer" className="partner-link">
                <img src="https://wmaplus.com/assets/logo.png" alt="WMA Plus" className="partner-logo" />
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Video Presentation Section */}
      <section style={{ padding: '5rem 0', background: 'rgba(0,0,0,0.15)', borderTop: '1px solid var(--border-color)', borderBottom: '1px solid var(--border-color)' }}>
        <div className="container" style={{ textAlign: 'center' }}>
          <h2 style={{ fontSize: '2.5rem', marginBottom: '1rem' }}>{t('video_title')}<span>{t('video_span')}</span></h2>
          <p style={{ color: 'var(--text-muted)', maxWidth: '600px', margin: '0 auto 2.5rem', lineHeight: '1.6' }}>
            {t('video_subtitle')}
          </p>
          <div className="video-wrapper" style={{ position: 'relative', paddingBottom: '56.25%', height: 0, overflow: 'hidden', maxWidth: '100%', margin: '0 auto', borderRadius: '1.5rem', border: '1px solid var(--border-color)', boxShadow: '0 15px 35px rgba(0,0,0,0.3)' }}>
            <iframe 
              src={`https://www.youtube.com/embed/${selectedVideoId}?autoplay=1&mute=1`} 
              title="WMA United Africa Presentation" 
              frameBorder="0" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
              allowFullScreen
              style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', borderRadius: '1.5rem', border: 'none' }}
            ></iframe>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer">
        <div className="container footer-grid">
          <div className="footer-col brand-col">
            <div className="footer-logo">
              <img src="./logo_ua.png" alt="WMA UA Logo" className="footer-logo-img" />
              <span className="logo-text" style={{ fontSize: '1.25rem' }}>WMA UNITED AFRICA</span>
            </div>
            <p className="footer-desc">
              {t('footer_desc')}
            </p>
          </div>

          <div className="footer-col">
            <h4 className="footer-title">{t('footer_nav')}</h4>
            <ul className="footer-links">
              <li><a href="#home" onClick={(e) => { e.preventDefault(); setActiveTab('home'); }}>{t('nav_home')}</a></li>
              <li><a href="#artists" onClick={(e) => { e.preventDefault(); setActiveTab('artists'); }}>{t('nav_artists')}</a></li>
              <li><a href="#distributions" onClick={(e) => { e.preventDefault(); setActiveTab('distributions'); }}>{t('nav_releases')}</a></li>
            </ul>
          </div>

          <div className="footer-col">
            <h4 className="footer-title">WMA Hub</h4>
            <ul className="footer-links">
              <li><a href="https://wmahub.com" target="_blank" rel="noopener noreferrer">{t('nav_join')} WMA Hub <ExternalLink size={12} style={{ marginLeft: '4px', verticalAlign: 'middle' }} /></a></li>
              <li><a href="https://wmahub.com/auth/select-role.php" target="_blank" rel="noopener noreferrer">{t('footer_member_space')}</a></li>
            </ul>
          </div>

          <div className="footer-col">
            <h4 className="footer-title">{t('footer_contacts')}</h4>
            <ul className="footer-contacts">
              <li>
                <a href="mailto:info@wmahub.com" className="contact-link">
                  <Mail size={16} />
                  <span>info@wmahub.com</span>
                </a>
              </li>
              <li>
                <a href="https://wa.me/256743297668" target="_blank" rel="noopener noreferrer" className="contact-link">
                  <MessageCircle size={16} />
                  <span>+256 743 297 668</span>
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div className="container footer-bottom">
          <div className="footer-bottom-content">
            <p>{t('footer_rights')}</p>
            <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '0.25rem' }}>
              {t('footer_visits', { total: visitStats.total, today: visitStats.today })}
            </p>
          </div>
          <div className="footer-brand-container" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.25rem' }}>
            <span className="footer-brand">We move, WMAFam</span>
            <span className="footer-from" style={{ fontSize: '0.8rem', color: 'var(--text-muted)', fontWeight: 700, letterSpacing: '0.05em', textTransform: 'uppercase' }}>From WMA HUB</span>
          </div>
          <div className="footer-bottom-content" style={{ textAlign: 'right' }}>
            <p className="dev-credit">{t('footer_dev')}<a href="https://nextbytechno.com/" target="_blank" rel="noopener noreferrer">Next Byte Technology</a></p>
          </div>
        </div>
      </footer>
    </>
  );
}
