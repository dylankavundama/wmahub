-- Migration pour ajouter les informations de compte bancaire et mobile money
-- Exécuter ce script pour permettre la gestion des paiements employés

-- Ajouter les colonnes pour les comptes bancaires et mobile money
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS bank_account VARCHAR(100),
ADD COLUMN IF NOT EXISTS mobile_money_account VARCHAR(100);
