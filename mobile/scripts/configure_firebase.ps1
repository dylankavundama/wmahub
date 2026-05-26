# Configuration Firebase / FlutterFire pour WMA UA (mobile)
# Prérequis : Node.js, compte Google connecté à Firebase
#
#   npm install -g firebase-tools
#   firebase login
#   dart pub global activate flutterfire_cli

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "=== FlutterFire configure (projet uawma-70e70) ===" -ForegroundColor Cyan

# Connexion requise une fois : firebase login
dart pub global activate flutterfire_cli
& "$env:LOCALAPPDATA\Pub\Cache\bin\flutterfire.bat" configure `
  --project=uawma-70e70 `
  --platforms=android,ios `
  --android-package-name=com.ua.wmahub `
  --ios-bundle-id=com.ua.wmahub `
  --out=lib/firebase/firebase_options.dart `
  --yes

Write-Host ""
Write-Host "Fichiers mis à jour :" -ForegroundColor Green
Write-Host "  - lib/firebase/firebase_options.dart"
Write-Host "  - android/app/google-services.json"
Write-Host "  - ios/Runner/GoogleService-Info.plist"
Write-Host ""
Write-Host "Firebase Console > Authentication > Sign-in method :" -ForegroundColor Yellow
Write-Host "  - Email/Password : activé"
Write-Host "  - Apple : activé (iOS)"
Write-Host "  - Google : désactivé (réservé au site web PHP)"
