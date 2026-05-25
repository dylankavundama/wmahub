# GUIDE D'ENREGISTREMENT D'ÉCRAN - WMA Hub for iOS

**Objectif**: Créer un enregistrement vidéo professionnel conformant aux exigences Apple Directive 2.1

---

## PRÉREQUIS

### Matériel Requis
- ✅ iPhone 15 Pro + (recommandé) ou iPhone 14+
- ✅ macOS Monterey+ ou Windows avec QuickTime
- ✅ Câble USB-C pour connexion
- ✅ Batterie complètement chargée
- ✅ WiFi stable

### Software
- ✅ iOS 17.5 ou plus récent
- ✅ WMA Hub v1.1.0 (Build 8) installée
- ✅ Do Not Disturb activé
- ✅ Mode Avion OFF (pour internet)
- ✅ Comptes de test pré-créés et testés

---

## ÉTAPE 1: PRÉPARATION DE L'APPAREIL

### A. Configuration iOS
```bash
1. Ouvrir Settings
2. Désactiver Spotlight Search (pour écran propre)
3. Désactiver tous les badges (sauf WMA Hub)
4. Activer Do Not Disturb pour éviter interruptions
5. Augmenter luminosité à 100%
6. Activer Mode Sombre (Dark Mode)
7. Vérifier WiFi activé et stable
```

### B. Configuration de l'Application
```bash
1. Lancer WMA Hub
2. Si déjà loggé: Se déconnecter
3. Vider le cache: Settings → WMA Hub → Clear Cache
4. Redémarrer l'application
5. Attendre splash screen (important à montrer!)
```

### C. Préparer les Données de Test
```
Test Account:
- Email: test.artist@wmahub.ua
- Password: TestArtist2024!
- Rôle: Artiste
- Déjà configuré avec 3 projets test
- Revenus visibles: $2,450.50 USD
```

---

## ÉTAPE 2: DÉMARRER L'ENREGISTREMENT

### Sur macOS (USB Connection)
```bash
1. Connecter iPhone via USB-C
2. Déverrouiller iPhone et faire confiance à l'ordinateur
3. Ouvrir QuickTime Player
4. File → New Movie Recording
5. Sélectionner caméra: iPhone (en dropdown)
6. Cliquer rouge REC pour démarrer
7. L'écran iPhone s'affichera en direct
```

### Sur macOS (AirPlay)
```bash
1. S'assurer iPhone et Mac sont sur le même WiFi
2. Ouvrir QuickTime Player
3. iPhone: Control Center → Screen Recording (long press) → AirPlay → Mac
4. QuickTime reconnaîtra l'enregistrement automatique
```

### Sur Windows
```bash
1. Connecter iPhone via USB
2. Utiliser: iOS Screen Recorder (gratuit) ou similar
3. Alternative: Utiliser Mac ou service cloud
```

---

## ÉTAPE 3: SCÉNARIO D'ENREGISTREMENT (90 secondes)

### Timeline Complète

#### ⏱️ 0:00-0:05 | Lancement & Splash
```
ACTION:
1. Appuyer sur icône WMA Hub
2. Laisser splash screen se charger (2-3 sec)
3. Montrer animation du loader
4. Attendre transition vers login screen

VISIBILITÉ:
- Logo WMA Hub clair
- Animation smooth du spinner
- Pas de freeze ou ralentissements
```

#### ⏱️ 0:05-0:20 | Processus d'Authentification Apple Sign-In

```
ACTION:
1. Tap bouton "Sign In with Apple"
2. Montrer l'interface d'authentification Apple
3. Montrer les options:
   - Share My Email
   - Hide My Email
4. Sélectionner "Share My Email" (pour test)
5. Montrer écran de sélection de rôle après succès

POINTS CLÉS:
- Permission flow clair
- Transition fluide vers sélection de rôle
- Pas d'erreurs
- 15 secondes maximum pour cette partie
```

#### ⏱️ 0:20-0:35 | Dashboard Principal & Navigation

```
ACTION:
1. Tab "Accueil" sélectionné par défaut
2. Scroller down pour montrer:
   - Vue synthétique (Top stats)
   - Derniers projets (3 cartes)
   - Quick actions buttons
3. Montrer Bottom Navigation Bar:
   - Accueil (Accueil icon)
   - Distributions (Music icon)
   - Services (Grid icon)
   - Profil (User icon)
4. Cliquer sur chaque onglet rapidement (2 sec chacun)

POINTS CLÉS:
- Dashboard chargé correctement
- Données visibles et lisibles
- Animations fluides
- Pas de loading skeleton excessif
```

#### ⏱️ 0:35-0:50 | Créer un Projet (Multi-étape)

```
ACTION:
1. Revenir à "Accueil"
2. Taper bouton "Créer un Projet" (bouton orange)
3. Écran 1 - Informations Basiques:
   - Remplir "Titre": "New Single 2026"
   - Remplir "Artiste": "Test Artist"
4. Taper "Suivant"
5. Écran 2 - Image/Genre:
   - Taper "Ajouter Image"
   - Sélectionner une image du test data
   - Sélectionner Genre: "Pop"
6. Taper "Suivant"
7. Écran 3 - Description:
   - Montrer champ de texte
   - Pré-remplir avec texte (pas à soumettre)
8. Taper "Aperçu"
9. Montrer l'écran de confirmation
10. Taper "Annuler" (ne pas vraiment soumettre)

POINTS CLÉS:
- Form fonctionne correctement
- File picker fonctionne
- Image upload preview visible
- Pas d'erreurs de validation
- 15 secondes pour cette section
```

#### ⏱️ 0:50-1:10 | Fonctionnalités Supplémentaires

```
ACTION:
1. Aller à "Services" tab
2. Scroller le carrousel de services
3. Montrer les cartes de service
4. Cliquer sur "À Propos" (if available)
5. Afficher contenu
6. Retour en arrière (swipe ou bouton)
7. Aller à "Distributions"
8. Afficher les distributions existantes
9. Scroller pour voir plusieurs projets

POINTS CLÉS:
- Carousel fonctionne (swipe fluid)
- Chargement des images rapide
- Navigation fluide
- Pas de blanc/empty states
```

#### ⏱️ 1:10-1:25 | Profil & Suppression de Compte

```
ACTION:
1. Taper "Profil" tab
2. Afficher informations du profil
3. Scroller down
4. Taper "Paramètres"
5. Afficher options:
   - Modifier profil
   - Notifications settings
   - Privacy settings
6. Chercher "Supprimer mon compte"
7. Taper le bouton
8. Montrer la confirmation dialogue:
   - "Êtes-vous sûr?"
   - "Saisir mot de passe pour confirmer"
9. Saisir le mot de passe
10. Montrer le message de succès
11. Montrer l'écran de login à nouveau

POINTS CLÉS:
- Delete confirmation flow complet
- Sécurité: demande de mot de passe
- Pas de bypass
- Message de succès clair
- Retour à login après suppression
```

#### ⏱️ 1:25-1:30 | Retour & Fin

```
ACTION:
1. Si screen time le permet, retour au login
2. Montrer écran final "Bienvenue"
3. Laisser fade to black
4. FIN DE L'ENREGISTREMENT

POINTS CLÉS:
- Fin professionnelle
- Pas d'erreurs à la fermeture
- Pas de crash
```

---

## ÉTAPE 4: ARRÊTER L'ENREGISTREMENT & EXPORT

### Dans QuickTime
```bash
1. Command + Control + Espace (stop recording)
2. Vérifier l'enregistrement vidéo s'affiche
3. File → Save As
4. Format: MP4 (Container) / H.264 (Codec)
5. Quality: High
6. Resolution: 1170 x 2532 (native iPhone 15)
7. Nommer: "WMA_Hub_v1.1.0_Demo_iOS17.5.mp4"
```

### Vérifications Finales
```bash
✅ Durée: 60-90 secondes (idéal: 75 sec)
✅ Résolution: 1170 x 2532 (pas upscalé)
✅ Codec: H.264
✅ Container: MP4 (ou MOV)
✅ Bitrate: 25-35 Mbps
✅ Frame rate: 60 FPS
✅ Pas de watermark
✅ Pas de texte superposé
✅ Audio: Complet (avec sons système)
```

### Compression (si nécessaire)
```bash
# Si fichier > 500MB, compresser légèrement:
ffmpeg -i input.mp4 -vcodec h264 -crf 23 output.mp4

# CRF: 18-28 (23 = bon compromis qualité/taille)
```

---

## ÉTAPE 5: UPLOAD À APP STORE CONNECT

### Dans App Store Connect
```bash
1. Aller à: My Apps → WMA Hub → Build
2. Section: "App Preview" (pas Screenshots)
3. Cliquer "Add App Preview"
4. Sélectionner dispositif: "iPhone 6.7-inch"
5. Drag-drop ou sélectionner le fichier MP4
6. Attendre upload (peut prendre 5-10 min)
7. Vérifier la miniature en preview
8. Confirmer les prévisualisation
```

### Metadata to Include
```bash
Display: "App walkthrough demonstrating:
- Onboarding and authentication
- Project creation workflow
- Distribution management
- Account deletion feature
- Full feature overview"
```

---

## CHECKLIST FINALE

### Avant d'Enregistrer
- [ ] iPhone chargé à 100%
- [ ] WiFi stable testé
- [ ] App lancée et fonctionnelle
- [ ] Comptes de test vérifiés
- [ ] Do Not Disturb ON
- [ ] Dark Mode ON
- [ ] Luminosité 100%

### Pendant l'Enregistrement
- [ ] Audio clair (pas de bruits de fond)
- [ ] Gestes naturels et fluides
- [ ] Pas de long time dans loading
- [ ] Pas d'erreurs visibles
- [ ] Transitions naturelles
- [ ] Zoom adapté (lisible)

### Après l'Enregistrement
- [ ] Durée 60-90 secondes
- [ ] Pas de freeze/crash
- [ ] Qualité HD 1170x2532
- [ ] Son clair et continu
- [ ] Format MP4/MOV correct
- [ ] Fichier <500MB

### App Store Connect
- [ ] Upload successful
- [ ] Miniature visible
- [ ] Métadonnées correctes
- [ ] Pas d'erreurs de validation

---

## CONSEILS PRO

### 1. Pratique Avant
```
- Faire 2-3 enregistrements de test
- Observer pour les erreurs
- Refaire si crash/freeze
```

### 2. Gestes Naturels
```
- Tap: Un doigt, rapide
- Swipe: Mouvement fluide
- Scroll: Vitesse modérée
- Double-tap: Pour zoom (si pertinent)
```

### 3. Timing
```
- Pas trop rapide (utilisateur pas suivi)
- Pas trop lent (ennuyeux)
- Vitesse réelle: Ce qu'un utilisateur ferait
```

### 4. Audio
```
- Laisser actif les sons système (button clicks)
- Pas de narration/voiceover requis
- Pas de musique de fond
- Microphone clair (pas d'écho)
```

### 5. Si Erreur Arrive
```
- Prendre une deuxième prise
- Couper la partie erreur et recommencer
- Ne pas montrer les erreurs à Apple
```

---

## SUPPORT

Questions sur l'enregistrement?
- Consulter: https://support.apple.com/en-us/HT207935
- Email: compliance@wmahub.ua

**Bon enregistrement! 🎬**
