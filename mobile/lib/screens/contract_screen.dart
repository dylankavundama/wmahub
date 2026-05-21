import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:ui';
import '../utils/app_theme.dart';
import 'main_navigation.dart';

class ContractScreen extends StatefulWidget {
  final int userId;
  final VoidCallback onSigned;

  const ContractScreen({
    super.key,
    required this.userId,
    required this.onSigned,
  });

  @override
  State<ContractScreen> createState() => _ContractScreenState();
}

class _ContractScreenState extends State<ContractScreen> {
  bool _isSigned = false;

  Future<void> _signContract() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('contract_signed_${widget.userId}', true);
    widget.onSigned();
    if (mounted) {
      if (Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => const MainNavigation(),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Background Image
          Image.network(
            'https://wmahub.com/asset/aspi.jpg',
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) =>
                Container(color: Colors.black),
          ),

          // Blur Overlay
          BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.6),
                    Colors.black.withValues(alpha: 0.9),
                  ],
                ),
              ),
            ),
          ),

          SafeArea(
            child: Column(
              children: [
                const SizedBox(height: 20),
                const Text(
                  'CONTRAT ARTISTE',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                    letterSpacing: 3,
                  ),
                ).animate().fadeIn().slideY(begin: -0.2),
                const SizedBox(height: 10),
                const Text(
                  'Signature obligatoire pour accéder au tableau de bord',
                  style: TextStyle(color: AppTheme.textGrey, fontSize: 12),
                ).animate().fadeIn(delay: 200.ms),
                const SizedBox(height: 30),

                Expanded(
                  child: Container(
                    margin: const EdgeInsets.symmetric(
                      horizontal: 20,
                      vertical: 10,
                    ),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: AppTheme.cardColor.withValues(alpha: 0.8),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: const SingleChildScrollView(
                      child: Text(
                        '''CONDITIONS GÉNÉRALES DE DISTRIBUTION – WMA HUB

En créant un compte de distribution sur la plateforme WMA HUB, l’Artiste accepte sans réserve les présentes conditions.

1. Objet
WMA HUB fournit à l’Artiste un service de distribution musicale sur les plateformes de streaming et de téléchargement partenaires, ainsi que des outils de suivi et de gestion des revenus.

2. Droits et responsabilités
L’Artiste déclare être le propriétaire légal des œuvres distribuées ou disposer de tous les droits nécessaires (droits d’auteur, droits voisins, autorisations).
WMA HUB n’est pas responsable des litiges liés à la propriété des œuvres.

3. Partage des revenus
- WMA HUB conserve 15 % des revenus nets générés par les œuvres distribuées.
- L’Artiste reçoit 85 % des revenus nets.
Ce pourcentage est fixe et s’applique à l’ensemble des revenus générés via WMA HUB.

4. Paiement et retrait des revenus
- Les revenus sont retirables tous les deux (2) mois.
- Les demandes de retrait se font exclusivement via le compte WMA HUB de l’Artiste.
- Les paiements sont soumis aux délais techniques des plateformes partenaires.

5. Rapports et statistiques
- Les rapports financiers et statistiques sont mis à disposition tous les trois (3) mois.
- Les données incluent notamment : streams, téléchargements et revenus estimés.

6. Durée
Le présent accord entre en vigueur dès la création du compte de distribution et reste valable tant que le compte est actif sur WMA HUB.

7. Résiliation
WMA HUB se réserve le droit de suspendre ou résilier un compte en cas de :
- Violation des présentes conditions,
- Fraude,
- Diffusion de contenu illégal ou non autorisé.
L’Artiste peut demander la fermeture de son compte conformément aux procédures internes de WMA HUB.

8. Acceptation
La création du compte et l’utilisation des services de WMA HUB valent acceptation totale et définitive des présentes conditions générales.

WMA HUB
Plateforme de distribution et de management musical''',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 14,
                          height: 1.5,
                        ),
                      ),
                    ),
                  ).animate().fadeIn(delay: 400.ms).scale(),
                ),

                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.5),
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(30),
                    ),
                  ),
                  child: Column(
                    children: [
                      Theme(
                        data: ThemeData(unselectedWidgetColor: Colors.white54),
                        child: CheckboxListTile(
                          value: _isSigned,
                          onChanged: (val) {
                            setState(() => _isSigned = val ?? false);
                          },
                          title: const Text(
                            "Je valide les termes du contrat",
                            style: TextStyle(color: Colors.white, fontSize: 13),
                          ),
                          activeColor: AppTheme.primaryColor,
                          checkColor: Colors.white,
                          controlAffinity: ListTileControlAffinity.leading,
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                      const SizedBox(height: 20),
                      SizedBox(
                        width: double.infinity,
                        height: 55,
                        child: ElevatedButton(
                          onPressed: _isSigned ? _signContract : null,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primaryColor,
                            disabledBackgroundColor: Colors.white10,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          child: Text(
                            'VALIDER ET CONTINUER',
                            style: TextStyle(
                              color: _isSigned ? Colors.white : Colors.white38,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 600.ms).slideY(begin: 0.5),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
