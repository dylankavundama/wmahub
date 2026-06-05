import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:ui';
import 'dart:convert';
import 'dart:typed_data';
import '../utils/app_theme.dart';
import 'main_navigation.dart';
import '../services/wordpress_service.dart';

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
  final List<Offset?> _signaturePoints = [];
  bool _isLoadingStatus = true;
  bool _hasSignedDb = false;
  String _dbSignatureUrl = '';

  bool get _isSigned => _signaturePoints.any((p) => p != null);

  @override
  void initState() {
    super.initState();
    _checkDbContractStatus();
  }

  Future<void> _checkDbContractStatus() async {
    final status = await WordPressService().checkContractStatus(widget.userId);
    if (status['success'] == true && status['signed'] == true) {
      if (mounted) {
        setState(() {
          _hasSignedDb = true;
          _dbSignatureUrl = status['signature'] ?? '';
          _isLoadingStatus = false;
        });
      }
    } else {
      if (mounted) {
        setState(() {
          _isLoadingStatus = false;
        });
      }
    }
  }

  Future<String?> _exportSignatureToBase64() async {
    if (_signaturePoints.isEmpty) return null;
    
    try {
      final recorder = PictureRecorder();
      final canvas = Canvas(recorder);
      
      // Arrière-plan blanc pour le contraste de la signature
      final paintBg = Paint()..color = Colors.white;
      canvas.drawRect(const Rect.fromLTWH(0, 0, 500, 250), paintBg);
      
      final paint = Paint()
        ..color = Colors.black
        ..strokeCap = StrokeCap.round
        ..strokeWidth = 4.0;

      // Calculer la boîte englobante pour centrer et adapter la signature
      double minX = 99999;
      double maxX = -99999;
      double minY = 99999;
      double maxY = -99999;
      
      bool hasPoints = false;
      for (final p in _signaturePoints) {
        if (p != null) {
          hasPoints = true;
          if (p.dx < minX) minX = p.dx;
          if (p.dx > maxX) maxX = p.dx;
          if (p.dy < minY) minY = p.dy;
          if (p.dy > maxY) maxY = p.dy;
        }
      }
      
      if (!hasPoints) return null;
      
      double width = maxX - minX;
      double height = maxY - minY;
      if (width < 1) width = 1;
      if (height < 1) height = 1;
      
      const double targetW = 450;
      const double targetH = 200;
      double scale = targetW / width;
      if (targetH / height < scale) {
        scale = targetH / height;
      }
      
      final double offsetX = 25 + (targetW - width * scale) / 2 - minX * scale;
      final double offsetY = 25 + (targetH - height * scale) / 2 - minY * scale;
      
      for (int i = 0; i < _signaturePoints.length - 1; i++) {
        if (_signaturePoints[i] != null && _signaturePoints[i + 1] != null) {
          final p1 = Offset(
            _signaturePoints[i]!.dx * scale + offsetX,
            _signaturePoints[i]!.dy * scale + offsetY,
          );
          final p2 = Offset(
            _signaturePoints[i + 1]!.dx * scale + offsetX,
            _signaturePoints[i + 1]!.dy * scale + offsetY,
          );
          canvas.drawLine(p1, p2, paint);
        }
      }
      
      final picture = recorder.endRecording();
      final img = await picture.toImage(500, 250);
      final byteData = await img.toByteData(format: ImageByteFormat.png);
      if (byteData == null) return null;
      final pngBytes = byteData.buffer.asUint8List();
      final base64String = base64Encode(pngBytes);
      return 'data:image/png;base64,$base64String';
    } catch (e) {
      debugPrint("Error exporting signature: $e");
      return null;
    }
  }

  Future<void> _signContract() async {
    HapticFeedback.mediumImpact();
    
    final base64Sig = await _exportSignatureToBase64();
    if (base64Sig == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Une erreur est survenue lors de l'export de la signature")),
      );
      return;
    }

    setState(() {
      _isLoadingStatus = true;
    });

    final success = await WordPressService().saveContractSignature(widget.userId, base64Sig);

    if (success) {
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
    } else {
      if (mounted) {
        setState(() {
          _isLoadingStatus = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Échec de l'enregistrement de la signature. Veuillez réessayer.")),
        );
      }
    }
  }

  void _continueToDashboard() async {
    HapticFeedback.mediumImpact();
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
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.8),
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(30),
                    ),
                    border: const Border(
                      top: BorderSide(color: Colors.white10),
                    ),
                  ),
                  child: _isLoadingStatus
                      ? const Center(
                          child: Padding(
                            padding: EdgeInsets.symmetric(vertical: 40.0),
                            child: CircularProgressIndicator(color: AppTheme.primaryColor),
                          ),
                        )
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text(
                                  "SIGNATURE DE L'ARTISTE",
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 1.2,
                                  ),
                                ),
                                if (!_hasSignedDb && _isSigned)
                                  TextButton.icon(
                                    onPressed: () {
                                      HapticFeedback.mediumImpact();
                                      setState(() {
                                        _signaturePoints.clear();
                                      });
                                    },
                                    icon: const Icon(Icons.clear_rounded, size: 16, color: Colors.redAccent),
                                    label: const Text(
                                      "Effacer",
                                      style: TextStyle(color: Colors.redAccent, fontSize: 12, fontWeight: FontWeight.bold),
                                    ),
                                    style: TextButton.styleFrom(
                                      padding: EdgeInsets.zero,
                                      minimumSize: Size.zero,
                                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Container(
                              height: 140,
                              width: double.infinity,
                              decoration: BoxDecoration(
                                color: _hasSignedDb ? Colors.white : AppTheme.cardColor,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                  color: _hasSignedDb 
                                      ? Colors.white10 
                                      : (_isSigned ? AppTheme.primaryColor.withOpacity(0.5) : Colors.white10),
                                  width: 1.5,
                                ),
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(16),
                                child: _hasSignedDb
                                    ? (_dbSignatureUrl.startsWith('data:image')
                                        ? Image.memory(
                                            base64Decode(_dbSignatureUrl.split(',').last),
                                            fit: BoxFit.contain,
                                          )
                                        : const Center(
                                            child: Text(
                                              "Contrat déjà signé",
                                              style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold),
                                            ),
                                          ))
                                    : GestureDetector(
                                        onPanStart: (details) {
                                          HapticFeedback.lightImpact();
                                          setState(() {
                                            _signaturePoints.add(details.localPosition);
                                          });
                                        },
                                        onPanUpdate: (details) {
                                          setState(() {
                                            _signaturePoints.add(details.localPosition);
                                          });
                                        },
                                        onPanEnd: (details) {
                                          HapticFeedback.lightImpact();
                                          setState(() {
                                            _signaturePoints.add(null);
                                          });
                                        },
                                        child: CustomPaint(
                                          painter: SignaturePainter(points: _signaturePoints),
                                          size: Size.infinite,
                                        ),
                                      ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            Center(
                              child: Text(
                                _hasSignedDb
                                    ? "Contrat signé enregistré avec succès"
                                    : (_isSigned
                                        ? "Signature enregistrée"
                                        : "Dessinez votre signature tactile dans le cadre ci-dessus"),
                                style: TextStyle(
                                  color: _hasSignedDb
                                      ? Colors.green
                                      : (_isSigned ? AppTheme.primaryColor : Colors.white38),
                                  fontSize: 11,
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                            SizedBox(
                              width: double.infinity,
                              height: 55,
                              child: ElevatedButton(
                                onPressed: _hasSignedDb 
                                    ? _continueToDashboard 
                                    : (_isSigned ? _signContract : null),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppTheme.primaryColor,
                                  disabledBackgroundColor: Colors.white10,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                ),
                                child: Text(
                                  _hasSignedDb ? 'CONTINUER' : 'VALIDER ET CONTINUER',
                                  style: TextStyle(
                                    color: (_hasSignedDb || _isSigned) ? Colors.white : Colors.white38,
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

class SignaturePainter extends CustomPainter {
  final List<Offset?> points;

  SignaturePainter({required this.points});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppTheme.primaryColor
      ..strokeCap = StrokeCap.round
      ..strokeWidth = 3.0;

    for (int i = 0; i < points.length - 1; i++) {
      if (points[i] != null && points[i + 1] != null) {
        canvas.drawLine(points[i]!, points[i + 1]!, paint);
      }
    }
  }

  @override
  bool shouldRepaint(SignaturePainter oldDelegate) => true;
}
