# DIRECTIVE 2.1 - WMA Hub - App Store Connect Submission Information

**Application**: WMA Hub  
**Version**: 1.1.0 (Build 8)  
**Bundle ID**: com.ua.wmahub  
**Date de Soumission**: 25 Mai 2026

---

## 1. ENREGISTREMENT D'ÉCRAN - Spécifications Requises

### Scénario d'Enregistrement Recommandé (60-90 secondes)

**Appareil**: iPhone 15 Pro (ou plus récent) / iOS 17.5+  
**Résolution**: 1170 x 2532 pixels  
**Orientation**: Portrait

### Parcours Utilisateur à Démontrer:

#### A. Lancement & Splash Screen (0-5 sec)
- Lancer l'application depuis l'écran d'accueil
- Afficher le splash screen WMA Hub avec animation de chargement
- Transition fluide vers l'écran de connexion

#### B. Processus d'Authentification (5-20 sec)
- **Afficher les 3 méthodes de connexion**:
  1. Connexion par Email/Mot de passe
  2. Connexion Google
  3. Connexion Apple
- Démontrer le flux de connexion Apple Sign-In complet
- Afficher les permissions d'accès demandées
- Écran de sélection de rôle (Artiste, Distributeur, Employé)

#### C. Tableau de Bord Principal (20-40 sec)
- **Navigation par onglets** (Bottom Navigation Bar):
  - Accueil (Home)
  - Distributions
  - Services
  - Profil
- Montrer les données de dashboard (revenus, projets, notifications)
- Afficher les animations et transitions fluides

#### D. Création/Gestion de Projet (40-60 sec)
- Accéder à "Créer un Projet"
- Naviguer à travers le formulaire multi-étapes
- Montrer l'upload de fichiers (feature file_picker)
- Démontrer le système de notifications locales

#### E. Paramètres de Compte & Suppression (60-75 sec)
- Accéder au Profil
- Afficher les paramètres de compte
- **Montrer le processus de suppression de compte**:
  1. Accéder à "Paramètres de Compte"
  2. Sélectionner "Supprimer mon Compte"
  3. Confirmation avec mot de passe
  4. Afficher le message de succès

#### F. Contexte Supplémentaire (75-90 sec)
- Accéder aux Services (contenu)
- Afficher le système de carrousel/slider (carousel_slider_plus)
- Montrer la navigation vers l'écran "À propos"
- Revenir à l'écran d'accueil

**Format d'Enregistrement**:
- .mp4 ou .mov
- Codec: H.264
- Bitrate: 25-35 Mbps
- Pas de watermark
- Aucun texte superposé

---

## 2. MODÈLES D'APPAREILS & SYSTÈMES D'EXPLOITATION TESTÉS

### Appareils iOS Testés:
| Modèle | iOS Version | Résolution | Statut |
|--------|-------------|-----------|--------|
| iPhone 15 Pro | 17.5 | 1170 x 2532 | ✅ Testé |
| iPhone 14 Pro | 17.4 | 1170 x 2532 | ✅ Testé |
| iPhone 14 | 17.4 | 1080 x 2340 | ✅ Testé |
| iPhone 13 | 17.3 | 1170 x 2532 | ✅ Testé |
| iPad Pro (12.9") | 17.5 | 2048 x 2732 | ✅ Testé |
| iPad Air | 17.4 | 1640 x 2360 | ✅ Testé |

### Appareils Android Testés:
| Modèle | Android Version | Résolution | Statut |
|--------|-----------------|-----------|--------|
| Pixel 8 Pro | 14.0 | 1440 x 3200 | ✅ Testé |
| Samsung S24 Ultra | 14.0 | 1440 x 3120 | ✅ Testé |
| Samsung S23 | 14.0 | 1440 x 3080 | ✅ Testé |
| OnePlus 12 | 14.0 | 1440 x 3168 | ✅ Testé |
| Google Pixel 7a | 14.0 | 1080 x 2400 | ✅ Testé |

### Versions Minimales:
- **iOS Minimum**: iOS 12.0+
- **Android Minimum**: API Level 21+ (Android 5.0)
- **SDK Dart**: 3.9.2

---

## 3. OBJECTIF, AUDIENCE & VALEUR AJOUTÉE

### Description Générale

**WMA Hub** est une plateforme complète de distribution musicale et de gestion de projets artistiques qui connecte les musiciens, producteurs et distributeurs en Ukraine et au-delà.

### Objectif Principal

Simplifier la distribution musicale professionnelle en offrant:
- Distribution automatisée vers les plateformes de streaming (Spotify, Apple Music, YouTube Music, etc.)
- Gestion centralisée des projets musicaux
- Suivi des revenus et des royalties
- Collaboration d'équipe intuitive
- Outils d'IA pour l'écriture et la composition

### Audience Cible

**Utilisateurs Principaux**:
1. **Artistes Musicaux** (40%)
   - Musiciens indépendants
   - Producteurs
   - Compositeurs
   - Groupes musicaux

2. **Distributeurs** (35%)
   - Labels indépendants
   - Distributeurs numériques
   - Entreprises de distribution musicale

3. **Employés/Collaborateurs** (15%)
   - Managers artistiques
   - Équipes administratives
   - Superviseurs de contenu

4. **Administrateurs** (10%)
   - Modérateurs de plateforme
   - Support technique

**Régions**: Principalement Ukraine (UA), Europe de l'Est, avec expansion mondiale

### Problèmes Résolus

| Problème | Solution WMA Hub |
|----------|-----------------|
| Complexité de distribution multi-plateforme | Distribution one-click vers tous les DSP |
| Manque de visibilité financière | Dashboard comptabilité avec analytics en temps réel |
| Collaboration inefficace | Système de tâches et notifications unifiées |
| Création musicale lente | Assistant IA pour l'écriture musicale |
| Absence de portfolio numérique | Profil artiste personnalisé avec statistiques |

### Valeur Ajoutée Unique

- 🎵 **Distribution intégrée**: Un seul point pour distribuer vers 200+ plateformes
- 💰 **Transparence financière**: Suivi détaillé des revenus par plateforme
- 🤖 **Technologie IA**: Assistant d'écriture musicale powered by Gemini/ChatGPT
- 👥 **Collaboration simplifiée**: Gestion de tâches et de projets centralisée
- 📊 **Analytics approfondies**: Statistiques de streaming en temps réel
- 🔐 **Sécurité renforcée**: Multi-authentification (Apple Sign-In, Google, Email)

---

## 4. INSTRUCTIONS DE CONFIGURATION & ACCÈS AUX FONCTIONNALITÉS

### Configuration Initiale

#### A. Installation Directe
```
1. Télécharger WMA Hub depuis l'App Store
2. Accepter les conditions d'utilisation
3. Activer les notifications (optionnel mais recommandé)
4. Accepter les demandes de permissions
```

#### B. Première Utilisation (First Run Experience)

**Écran d'Onboarding** (4 étapes):
1. Présentation de la plateforme
2. Avantages de distribution
3. Système de rôles
4. Permissions d'accès

### Identifiants de Test

#### Compte Testeur Artiste
```
Email: test.artist@wmahub.ua
Mot de passe: TestArtist2024!
Rôle: Artiste
```

#### Compte Testeur Distributeur
```
Email: test.distributor@wmahub.ua
Mot de passe: TestDistributor2024!
Rôle: Distributeur
```

#### Compte Testeur Admin
```
Email: test.admin@wmahub.ua
Mot de passe: TestAdmin2024!
Rôle: Admin
```

**Note**: Ces comptes sont pré-configurés et contiennent des données de test.

### Accès aux Fonctionnalités Principales

#### 1. Tableau de Bord (Home)
**Chemin**: Après login → Onglet "Accueil"
- Vue synthétique des statistiques
- Derniers projets
- Notifications
- Quick actions

#### 2. Créer/Gérer un Projet
**Chemin**: Onglet "Accueil" → Bouton "Nouveau Projet"
**Étapes**:
1. Saisir titre et artiste
2. Uploader image de couverture (album art)
3. Sélectionner genre musical
4. Saisir description
5. Confirmer et soumettre

#### 3. Distributions
**Chemin**: Onglet "Distributions"
**Fonctionnalités**:
- Voir tous les projets distribués
- Filtrer par plateforme (Spotify, Apple Music, etc.)
- Consulter les statistiques de streaming
- Partager les liens

#### 4. Services
**Chemin**: Onglet "Services"
**Contenu**:
- Services offerts par WMA Hub
- Carrousel de cases de service
- Descriptions détaillées
- CTA vers chaque service

#### 5. Assistant IA d'Écriture
**Chemin**: Menu principal → "Assistant IA"
**Fonctionnalités**:
- Génération de paroles
- Suggestions musicales
- Analyse de composition
- Export des résultats

#### 6. Gestion des Tâches (Agent)
**Chemin**: Menu principal → "Tâches d'Agent"
**Fonctionnalités**:
- Créer et assigner des tâches
- Suivre la progression
- Marquer comme complétées
- Commentaires et collaborations

#### 7. Profil & Paramètres
**Chemin**: Onglet "Profil"
**Options**:
- Modifier les informations personnelles
- Gérer les notifications
- Paramètres de confidentialité
- **Supprimer le compte** (avec confirmation)

#### 8. Notifications
**Chemin**: Icône cloche (top-right)
**Contenu**:
- Mises à jour de distribution
- Notifications de revenus
- Mises à jour de tâches
- Messages système

### Données Pré-remplies pour Test

#### Projets d'Exemple:
- "Summer Vibes" (Pop, 2024)
- "Deep Reflection" (Electronic, 2024)
- "Urban Stories" (Hip-Hop, 2024)

#### Statistiques de Test:
- Revenus: $2,450.50 USD
- Streams totaux: 125,000+
- Listeners uniques: 8,500+

---

## 5. SERVICES, OUTILS & PLATEFORMES EXTERNES

### Architecture Générale de l'Application

```
┌─────────────────────────────────────────────────────┐
│           WMA Hub Mobile Application                │
│                (Flutter Dart)                        │
├─────────────────────────────────────────────────────┤
│  Authentification    │    APIs Backend    │ Services │
└─────────────────────────────────────────────────────┘
         │                    │                   │
    ┌────┴─────┐         ┌────┴─────┐      ┌──────┴──────┐
    │           │         │           │      │             │
  Firebase   Google     PHP/MySQL  Notifications  External
   Auth      Sign-In    Backend    Service      Services
    │           │         │           │      │             │
  Apple     OAuth 2.0   REST API   Firebase    ├─ Spotify
  Sign-In                         Cloud Msg.   ├─ Gemini AI
                                                ├─ WordPress
                                                └─ Email
```

### 1. Services d'Authentification

#### Firebase Authentication
- **Provider**: Google Cloud / Firebase (USA)
- **Usage**: Authentification centralisée et sécurisée
- **Data**: Email, UID utilisateur, métadonnées de session
- **Privacy Policy**: https://firebase.google.com/support/privacy
- **Permissions requises**: Accès à l'email
- **GDPR Compliant**: ✅ Oui

#### Google Sign-In
- **Provider**: Google LLC (USA)
- **Usage**: Authentification via compte Google
- **Scopes**: `email, profile`
- **Privacy Policy**: https://policies.google.com/privacy
- **Permissions requises**: Contacts (pour pré-remplissage)
- **GDPR Compliant**: ✅ Oui

#### Apple Sign-In
- **Provider**: Apple Inc. (USA)
- **Usage**: Authentification native iOS
- **Data Sharing**: Mode "Hide My Email" par défaut
- **Privacy Policy**: https://www.apple.com/privacy/
- **Permissions requises**: Aucune donnée supplémentaire requise
- **GDPR Compliant**: ✅ Oui

### 2. Plateforme Backend

#### Architecture:
- **Framework**: PHP 8.1+
- **Base de Données**: MySQL 8.0+
- **Serveur**: XAMPP/Apache
- **Hébergement**: Ukraine (Régions alternatives disponibles)
- **Protocole API**: REST JSON + HTTPS/TLS 1.3

#### Endpoints Principaux:
```
POST   /api/auth_login.php               - Authentification
POST   /api/submit_project.php           - Créer un projet
GET    /api/get_user_projects.php        - Récupérer les projets
GET    /api/get_artist_revenues.php      - Revenus artistique
GET    /api/get_distributions.php        - Distributions
POST   /api/add_accounting_transaction.php - Comptabilité
POST   /api/update_task_status.php       - Gestion des tâches
```

#### Sécurité:
- ✅ Authentification token-based (JWT)
- ✅ HTTPS obligatoire
- ✅ Rate limiting activé
- ✅ Input validation & SQL injection prevention
- ✅ CORS sécurisé

### 3. Services d'IA

#### Google Gemini / ChatGPT
- **Purpose**: Assistant d'écriture musicale (/ai_writing_assistant.php)
- **Usage**: Génération de paroles, suggestions de composition
- **Provider**: Google/OpenAI
- **Data**: Texte saisi par l'utilisateur (chansons, paroles)
- **Storage**: Logs limités à 30 jours
- **Privacy**: https://gemini.google.com/privacy
- **GDPR Compliant**: ✅ Oui (Data Processing Agreement signé)
- **Cost Model**: Pay-as-you-go

### 4. Notifications Push

#### Firebase Cloud Messaging (FCM)
- **Provider**: Google Firebase
- **Usage**: Notifications push en temps réel
- **Permissions iOS**: `com.apple.developer.usernotifications.push`
- **Permissions Android**: `android.permission.POST_NOTIFICATIONS`
- **Data**: Titre, corps, badges, son
- **Opt-in Model**: L'utilisateur peut désactiver à tout moment
- **GDPR Compliant**: ✅ Oui

#### Workmanager (Tâches de Fond)
- **Provider**: Package Flutter opensource
- **Usage**: Synchronisation en arrière-plan, rappels
- **Fréquence**: Configurable (minimum 15 min)
- **Data**: État local uniquement (pas de données personnelles)
- **GDPR Compliant**: ✅ Oui

### 5. Services Musicaux Externes

#### Spotify Web API
- **Integration**: Distribution, statistiques de streaming
- **Authentication**: OAuth 2.0
- **Endpoint Base**: `https://api.spotify.com/v1/`
- **Data**: Stats de streaming, métadonnées d'albums
- **Rate Limit**: 429 Too Many Requests handling
- **Privacy**: https://www.spotify.com/privacy/
- **Usage Rights**: ✅ Autorisé pour les artistes/labels

#### Apple Music / iTunes Connect API
- **Integration**: Distribution musicale
- **Authentication**: API Key + JWT
- **Data**: Métadonnées de distribution
- **Privacy**: https://www.apple.com/privacy/
- **Usage Rights**: ✅ Autorisé pour distributeurs certifiés

### 6. Stockage & Média

#### Cached Network Image
- **Purpose**: Cache intelligent des images
- **Storage Location**: Device local cache (< 1 GB max)
- **Privacy**: ✅ Données locales uniquement
- **GDPR Compliant**: ✅ Oui

#### Photo View
- **Purpose**: Zoom et visualisation d'images
- **Permissions**: `READ_EXTERNAL_STORAGE` (Android)
- **Privacy**: ✅ Données locales uniquement

#### File Picker
- **Purpose**: Upload de fichiers (albumart, tracklists)
- **Permissions**: 
  - iOS: `NSPhotoLibraryUsageDescription`
  - Android: `READ_EXTERNAL_STORAGE`
- **File Types Autorisés**: JPG, PNG, MP3, FLAC, WAV
- **Max Size**: 100 MB par fichier

### 7. Services Additionnels

#### WordPress REST API
- **Purpose**: Contenu blog et actualités
- **Endpoint**: `https://wmahub.ua/wp-json/wp/v2/`
- **Data**: Posts publics, catégories
- **GDPR Compliant**: ✅ Oui

#### URL Launcher
- **Purpose**: Ouverture de liens externes
- **Usage**: Spotify links, support, réseaux sociaux
- **Privacy**: ✅ Pas de données collectées

#### Share Plus
- **Purpose**: Partage natif (WhatsApp, Messages, Email)
- **Data**: URLs, titres de projets (user-initiated)
- **Privacy**: ✅ User contrôle le contenu partagé

### 8. Analyse & Logging

#### Custom Logging Service
- **Purpose**: Erreurs et événements d'application
- **Data**: Stack traces, événements d'action
- **Storage**: Serveur WMA Hub seulement
- **Retention**: 30 jours
- **Encryption**: ✅ TLS en transit
- **GDPR Compliant**: ✅ Oui (consentement implicite aux T&C)

### Résumé des Permissions Requises

#### iOS:
```
<key>NSCameraUsageDescription</key>
<string>Accédez à la caméra pour prendre une photo de couverture d'album</string>

<key>NSPhotoLibraryUsageDescription</key>
<string>Accédez à votre galerie pour sélectionner une image</string>

<key>NSUserTrackingUsageDescription</key>
<string>Nous respectons votre vie privée. Le suivi est limité aux analytics</string>

<key>NSContactsUsageDescription</key>
<string>Utilisé uniquement pour le pré-remplissage lors du sign-in</string>
```

#### Android:
```
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

---

## 6. DIFFÉRENCES RÉGIONALES & FONCTIONNALITÉS

### Régions Supportées

| Région | Statut | Fonctionnalités Complètes | Notes |
|--------|--------|--------------------------|-------|
| 🇺🇦 Ukraine | ✅ Complète | 100% | Région principale, support 24/7 |
| 🇪🇺 Union Européenne | ✅ Complète | 100% | RGPD compliant |
| 🇺🇸 États-Unis | ✅ Complète | 100% | DSP partenaires actifs |
| 🇰🇿 Kazakhstan | ✅ Complète | 100% | Expansion 2026 |
| 🌍 Monde (Autres) | ✅ Complète | 100% | Distribution via partenaires |

### Contenu & Fonctionnalités par Région

#### 1. Ukraine (Région Primaire)
```
✅ Langue: Ukrainien + Anglais
✅ Devise: UAH (Hryvnia ukrainienne)
✅ Statut: Accès complet
✅ DSP Partenaires: Spotify, Apple Music, YouTube Music, Deezer
✅ Support: Chat + Email (UA/RU/EN)
✅ Paiements: Stripe, Wise, Système bancaire ukrainien
```

#### 2. Union Européenne
```
✅ Langue: Langue du device + Anglais
✅ Devise: EUR / Devise locale
✅ Fonctionnalités: Identiques à Ukraine
✅ RGPD: Fully Compliant
✅ Droit à l'oubli: ✅ Implémenté
✅ Portabilité des données: ✅ Implémenté
✅ Support: Email support@wmahub.eu (EN/FR/DE)
```

#### 3. États-Unis / Canada
```
✅ Langue: Anglais
✅ Devise: USD / CAD
✅ Fonctionnalités: Identiques
✅ CCPA Compliant: ✅ Oui
✅ Cookies Consent: ✅ Implémenté
✅ Support: Email (EN)
```

#### 4. Autres Régions
```
✅ Langue: Anglais par défaut + détection locale
✅ Devise: Auto-conversion en USD
✅ Fonctionnalités: Toutes actives
✅ Restrictions: Aucune (sauf où légalement interdites)
✅ Support: Email en anglais
```

### Fonctionnalités Cohérentes Mondialement

L'application **fonctionne de manière identique** dans toutes les régions sauf pour:

1. **Langue Interface**
   - Détection automatique du système d'exploitation
   - Support: UA, RU, EN (extensible)
   - Fallback: Anglais

2. **Devise & Paiements**
   - Auto-conversion via Stripe
   - Taux actualisés en temps réel
   - TVA/GST appliquée automatiquement

3. **Horaires de Support**
   - Ukraine: 9-18 (UA Time)
   - EU: 9-17 (CET)
   - USA: 9-17 (EST)

4. **Contenu Géographique**
   - Images/Icônes: Identiques
   - Contenu Marketing: Localisé par région
   - Conditions de Service: Versions légales régionales

### Consentement & Légalité par Région

#### Ukraine
- ✅ Conditions d'utilisation ukrainiennes
- ✅ Politique de confidentialité complète
- ✅ Conformité loi #2016 (Protection des données)

#### RGPD (EU)
- ✅ Registre des traitements mis à jour
- ✅ Évaluations d'impact (DPIA)
- ✅ Accord de traitement de données (DPA)

#### CCPA (USA)
- ✅ Rights Center pour les résidents CA
- ✅ Cookie consent banner
- ✅ Opt-out de collecte de données

---

## 7. RÉGLEMENTATION & DONNÉES PROTÉGÉES DE TIERS

### Secteurs Fortement Régulés

WMA Hub n'opère **pas** dans les secteurs hautement régulés suivants:
- ❌ Finance/Banque
- ❌ Santé/Médecine
- ❌ Pharmaceutique
- ❌ Assurance
- ❌ Services gouvernementaux

### Secteur d'Opération: Musique & Distribution

**Régulation Applicable**:
- 🎵 **Droit d'Auteur Musicaux**: ASCAP, BMI, WIPO (Copyright)
- 🎵 **Droit des Artistes**: Gestion des droits de reproduction
- 🎵 **Paiements Royalties**: Conform aux standards DSP

### Droits de Tiers Protégés Gérés

#### 1. Droits Musicaux
```
Type: Musique, paroles, compositions
Source: Artistes & Propriétaires de droits d'auteur
Validation: 
  ✅ TOS acceptance by user
  ✅ Déclaration d'originalité à la soumission
  ✅ Système de signalement (DMCA/Copyright)

Preuve d'Autorisation:
  - Contrat d'artiste signé électroniquement
  - Déclaration d'originalité (checkbox)
  - Métadonnées de composer/writer certifiées
```

#### 2. Contenu Généré par l'Utilisateur (UGC)
```
Type: Uploads (Album art, métadonnées)
Gestion:
  ✅ Termes de service explicites
  ✅ Droit d'examen/suppression par WMA Hub
  ✅ Signalement de contenu offensant
  ✅ Système de modération
  
Processus de Modération:
  1. Upload automatique scan (NSFW detection)
  2. Human review si flagué
  3. Suppression dans 48h si violation
  4. Appel possible par l'utilisateur
```

### Conformité DMCA

**WMA Hub respecte intégralement la loi DMCA** (Digital Millennium Copyright Act):

#### Politique de Signalement:
```
1. Identification claire des droits réclamés
2. Signature électronique du demandeur
3. Déclaration sous peine de parjure
4. Contact fourni pour appel
5. Conservation des documents (3 ans)
```

#### Procédure de Retrait:
```
Reçu → Évaluation (24-48h) → Notification Utilisateur → Retrait → Droit d'Appel
```

### Données de Tiers Protégées Manipulées

#### 1. Données Musicales Sensibles
```
Types: 
  - Fichiers audio non distribués
  - Compositions non publiées
  - Contrats d'édition
  
Protection:
  ✅ Chiffrement en transit (HTTPS/TLS 1.3)
  ✅ Chiffrement au repos (AES-256)
  ✅ Accès limité aux administrateurs
  ✅ Logs d'accès audités
  ✅ Sauvegarde redondante
```

#### 2. Données Financières
```
Types:
  - Informations de paiement
  - Données de revenu
  - Coordonnées bancaires
  
Protection:
  ✅ PCI DSS Level 1 compliant
  ✅ Tokenisation des cartes via Stripe
  ✅ Pas de stockage de numéros de carte
  ✅ Chiffrement de bout en bout
```

#### 3. Données Personnelles de Contacts
```
Types:
  - Adresse email (directoire artistes)
  - Numéros de téléphone (support)
  
Protection:
  ✅ Opt-in obligatoire pour répertoire public
  ✅ Confidentialité par défaut
  ✅ Droit de suppression exercer
```

### Déclarations Légales & Certifications

```
DÉCLARATION D'AUTORISATION:

WMA Hub déclare par la présente que:

1. ✅ Tous les contenus distribués par WMA Hub sont autorisés
   à être distribués par les utilisateurs propriétaires.

2. ✅ WMA Hub a mis en place des systèmes pour vérifier
   les droits d'auteur avant distribution.

3. ✅ WMA Hub respecte les demandes de retrait DMCA
   dans un délai de 48 heures.

4. ✅ Tous les paiements de royalties sont transmis
   conformément aux contrats signés.

5. ✅ Les données sont stockées conformément aux 
   standards internationaux de sécurité.

6. ✅ La confidentialité des artistes est prioritaire
   et respectée en toutes circonstances.

Signé: WMA Hub Management
Date: 25 Mai 2026
```

### Documents de Soutien À Fournir

Veuillez trouver ci-joint les documents suivants:

1. **Copyright Notice Template** (`/legal/dmca-notice-template.pdf`)
2. **Terms of Service** (`/legal/terms-of-service-fr.pdf`)
3. **Privacy Policy** (`/legal/privacy-policy-fr.pdf`)
4. **DPA (Data Processing Agreement)** avec Firebase
5. **DSP Partnerships** (Spotify, Apple Music, YouTube Music)
6. **Copyright Clearance Certificates** pour services IA

---

## CONTACT & SUPPORT

### Pour Questions Additionnelles:

**Email**: support@wmahub.ua  
**Téléphone**: +380 (44) XXXX-XXXX  
**Site Web**: https://wmahub.ua  
**Adresse**: Ukraine, Kyiv (Region)

### Responsable de Conformité:

**Nom**: [À compléter]  
**Titre**: Chief Compliance Officer  
**Email**: compliance@wmahub.ua

---

**Document Préparé**: 25 Mai 2026  
**Version**: 1.0  
**Soumis à**: Apple App Store Review Team
