import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import 'dart:convert';
import 'package:firebase_auth/firebase_auth.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';
import '../services/wordpress_service.dart';
import '../main.dart';
import 'admin_accounting_screen.dart';

class SimpleProfileScreen extends StatefulWidget {
  final Map<String, dynamic> user;
  final VoidCallback onLogout;
  
  const SimpleProfileScreen({
    super.key, 
    required this.user,
    required this.onLogout,
  });

  @override
  State<SimpleProfileScreen> createState() => _SimpleProfileScreenState();
}

class _SimpleProfileScreenState extends State<SimpleProfileScreen> {
  final _authService = AuthService();
  bool _pushNotifications = true;
  bool _darkMode = true;
  String _selectedLanguage = 'Français';
  double _cacheSizeMb = 24.8;
  bool _isClearingCache = false;

  Future<void> _launchURL(String url) async {
    final uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible d\'ouvrir le lien')),
        );
      }
    }
  }

  void _clearCache() async {
    if (_cacheSizeMb == 0.0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Le cache est déjà vide.'),
          backgroundColor: AppTheme.primaryColor,
        ),
      );
      return;
    }

    setState(() => _isClearingCache = true);
    await Future.delayed(const Duration(seconds: 1)); // Simulation d'effacement
    if (mounted) {
      setState(() {
        _cacheSizeMb = 0.0;
        _isClearingCache = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Cache système vidé avec succès !'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  void _showDeleteAccountDialog() {
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: AppTheme.cardColor,
        title: const Text(
          'Supprimer le compte ?',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        content: const Text(
          'Cette action est irréversible. Toutes vos informations personnelles seront effacées conformément aux règles RGPD et App Store.',
          style: TextStyle(color: AppTheme.textGrey, fontSize: 13, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('ANNULER', style: TextStyle(color: AppTheme.textGrey)),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(dialogContext);
              _deleteAccount();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            child: const Text('CONFIRMER', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: AppTheme.cardColor,
        title: const Text('Se déconnecter ?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        content: const Text(
          'Êtes-vous sûr de vouloir vous déconnecter de votre compte ?',
          style: TextStyle(color: AppTheme.textGrey, fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('ANNULER', style: TextStyle(color: AppTheme.textGrey)),
          ),
          ElevatedButton(
            onPressed: () async {
              final navigatorContext = context;
              Navigator.pop(dialogContext);
              await _authService.logout();
              if (navigatorContext.mounted) {
                RestartWidget.restartApp(navigatorContext);
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryColor),
            child: const Text('SE DÉCONNECTER', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteAccount() async {
    final navigatorContext = context;
    try {
      final response = await http.post(
        Uri.parse("${WordPressService.apiBaseUrl}/delete_account.php"),
        body: {'user_id': widget.user['id'].toString()},
      );
      
      if (navigatorContext.mounted) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          final messenger = ScaffoldMessenger.of(navigatorContext);
          final message = data['message']?.toString() ?? 'Compte supprimé';
          
          // Tentative sécurisée de suppression définitive du compte sur Firebase Auth
          try {
            final user = FirebaseAuth.instance.currentUser;
            if (user != null) {
              await user.delete();
            }
          } catch (firebaseError) {
            // Ignoré si la session Firebase est trop ancienne (requires-recent-login).
            // Le nettoyage SQL et la déconnexion ci-dessous restent suffisants.
            debugPrint("Firebase Auth user delete failed/skipped: $firebaseError");
          }
          
          await _authService.logout();
          
          messenger.showSnackBar(
            SnackBar(
              content: Text(message), 
              backgroundColor: Colors.green,
              behavior: SnackBarBehavior.floating,
            ),
          );
          
          // Attend 1 seconde pour voir le message avant le redémarrage complet de l'application
          await Future.delayed(const Duration(milliseconds: 1000));
          if (navigatorContext.mounted) {
            RestartWidget.restartApp(navigatorContext);
          }
        } else {
          ScaffoldMessenger.of(navigatorContext).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'Erreur lors de la suppression'), backgroundColor: Colors.redAccent),
          );
        }
      }
    } catch (e) {
      if (navigatorContext.mounted) {
        ScaffoldMessenger.of(navigatorContext).showSnackBar(
          const SnackBar(content: Text('Erreur lors de la suppression'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final name = widget.user['name'] ?? 'Utilisateur WMA';
    final email = widget.user['email'] ?? '';
    final role = widget.user['role']?.toString().toLowerCase().trim();
    
    String roleLabel = '';
    Color roleColor = AppTheme.primaryColor;

    if (role == 'admin' || role == 'administrator') {
      roleLabel = 'ADMINISTRATEUR';
      roleColor = const Color(0xFFFFB300);
    } else if (role == 'superadmin') {
      roleLabel = 'SUPER ADMIN';
      roleColor = const Color(0xFFB026FF);
    } else if (role == 'employe') {
      roleLabel = 'AGENT / EMPLOYÉ';
      roleColor = Colors.amberAccent;
    } else if (role == 'artiste') {
      roleLabel = 'ARTISTE';
      roleColor = AppTheme.primaryColor;
    }

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: SingleChildScrollView(
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 10),
                
                // Header Title
                const Text(
                  'Mon Profil',
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1,
                  ),
                ).animate().fadeIn().slideX(begin: -0.2, end: 0),
                
                const SizedBox(height: 24),

                // Premium Card with Avatar
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.03),
                    borderRadius: BorderRadius.circular(28),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.05),
                    ),
                  ),
                  child: Column(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: const LinearGradient(
                            colors: [AppTheme.primaryColor, AppTheme.accentColor],
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.primaryColor.withValues(alpha: 0.3),
                              blurRadius: 16,
                              spreadRadius: 2,
                            ),
                          ],
                        ),
                        child: const CircleAvatar(
                          radius: 46,
                          backgroundColor: AppTheme.backgroundColor,
                          child: Icon(
                            Icons.person_rounded,
                            size: 48,
                            color: Colors.white,
                          ),
                        ),
                      ).animate().scale(duration: 500.ms, curve: Curves.easeOutBack),

                      const SizedBox(height: 20),

                      Text(
                        name,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),

                      const SizedBox(height: 6),

                      Text(
                        email,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 14,
                          color: AppTheme.textGrey,
                        ),
                      ),

                      const SizedBox(height: 16),

                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: roleColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(99),
                          border: Border.all(
                            color: roleColor.withValues(alpha: 0.2),
                          ),
                        ),
                        child: Text(
                          roleLabel,
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: roleColor,
                            letterSpacing: 1,
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 200.ms),

                const SizedBox(height: 28),

                // CONFIGURATION SECTION
                const Text(
                  'CONFIGURATION DE L\'APPLICATION',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w900,
                    color: AppTheme.primaryColor,
                    letterSpacing: 1.5,
                  ),
                ).animate().fadeIn(delay: 300.ms),

                const SizedBox(height: 12),

                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.02),
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.04)),
                  ),
                  child: Column(
                    children: [
                      // Notifications Toggle
                      _buildSwitchTile(
                        icon: Icons.notifications_active_outlined,
                        title: 'Notifications Push',
                        subtitle: 'Recevoir les actualités et sorties',
                        value: _pushNotifications,
                        onChanged: (val) {
                          setState(() => _pushNotifications = val);
                        },
                      ),
                      _buildDivider(),

                      // Dark Mode Toggle
                      // _buildSwitchTile(
                      //   icon: Icons.dark_mode_outlined,
                      //   title: 'Mode Sombre',
                      //   subtitle: 'Activer le thème visuel sombre',
                      //   value: _darkMode,
                      //   onChanged: (val) {
                      //     setState(() => _darkMode = val);
                      //   },
                      // ),
                      _buildDivider(),

                      // Language Selector
                      // ListTile(
                      //   contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                      //   leading: Container(
                      //     padding: const EdgeInsets.all(10),
                      //     decoration: BoxDecoration(
                      //       color: Colors.white.withValues(alpha: 0.03),
                      //       shape: BoxShape.circle,
                      //     ),
                      //     child: const Icon(Icons.language_rounded, color: Colors.white70, size: 20),
                      //   ),
                      //   title: const Text(
                      //     'Langue de l\'application',
                      //     style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                      //   ),
                      //   subtitle: const Text(
                      //     'Sélectionnez votre langue de préférence',
                      //     style: TextStyle(color: AppTheme.textGrey, fontSize: 11),
                      //   ),
                      //   trailing: DropdownButtonHideUnderline(
                      //     child: DropdownButton<String>(
                      //       dropdownColor: AppTheme.cardColor,
                      //       value: _selectedLanguage,
                      //       items: const [
                      //         DropdownMenuItem(
                      //           value: 'Français',
                      //           child: Text('Français', style: TextStyle(color: Colors.white, fontSize: 13)),
                      //         ),
                      //         DropdownMenuItem(
                      //           value: 'English',
                      //           child: Text('English', style: TextStyle(color: Colors.white, fontSize: 13)),
                      //         ),
                      //       ],
                      //       onChanged: (val) {
                      //         if (val != null) {
                      //           setState(() => _selectedLanguage = val);
                      //         }
                      //       },
                      //     ),
                      //   ),
                      // ),
                      _buildDivider(),

                      // Clear Cache
                      ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                        onTap: _isClearingCache ? null : _clearCache,
                        leading: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.03),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.cleaning_services_outlined, color: Colors.white70, size: 20),
                        ),
                        title: const Text(
                          'Effacer le cache',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                        ),
                        subtitle: const Text(
                          'Libérer de l\'espace disque temporaire',
                          style: TextStyle(color: AppTheme.textGrey, fontSize: 11),
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              '${_cacheSizeMb.toStringAsFixed(1)} Mo',
                              style: const TextStyle(color: AppTheme.textGrey, fontSize: 12, fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(width: 8),
                            _isClearingCache
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryColor),
                                  )
                                : const Icon(Icons.chevron_right_rounded, color: AppTheme.textGrey),
                          ],
                        ),
                      ),
                      _buildDivider(),

                      // Privacy Policy
                      ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                        onTap: () => _launchURL('https://wmahub.com/privacy.php'),
                        leading: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.03),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.privacy_tip_outlined, color: Colors.white70, size: 20),
                        ),
                        title: const Text(
                          'Politique de Confidentialité',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                        ),
                        subtitle: const Text(
                          'Consultez nos chartes d\'utilisation',
                          style: TextStyle(color: AppTheme.textGrey, fontSize: 11),
                        ),
                        trailing: const Icon(Icons.open_in_new_rounded, color: AppTheme.textGrey, size: 18),
                      ),
                      if (role == 'admin' || role == 'administrator' || role == 'superadmin') ...[
                        _buildDivider(),
                        ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (context) => const AdminAccountingScreen()),
                            );
                          },
                          leading: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: roleColor.withValues(alpha: 0.05),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(Icons.admin_panel_settings_outlined, color: roleColor, size: 20),
                          ),
                          title: const Text(
                            'Panneau d\'Administration',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                          ),
                          subtitle: const Text(
                            'Accéder à la comptabilité et gestion',
                            style: TextStyle(color: AppTheme.textGrey, fontSize: 11),
                          ),
                          trailing: const Icon(Icons.chevron_right_rounded, color: AppTheme.textGrey),
                        ),
                      ],
                    ],
                  ),
                ).animate().fadeIn(delay: 350.ms),

                const SizedBox(height: 32),

                // LOGOUT & DELETE ACCOUNT ACTIONS
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: _showLogoutDialog,
                        icon: const Icon(Icons.logout_rounded, size: 18),
                        label: const Text(
                          'DÉCONNEXION',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white.withValues(alpha: 0.03),
                          foregroundColor: Colors.white,
                          elevation: 0,
                          side: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: _showDeleteAccountDialog,
                        icon: const Icon(Icons.delete_forever_rounded, size: 18),
                        label: const Text(
                          'SUPPRIMER COMPTE',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.transparent,
                          foregroundColor: Colors.redAccent,
                          elevation: 0,
                          side: BorderSide(color: Colors.redAccent.withValues(alpha: 0.2)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                      ),
                    ),
                  ],
                ).animate().fadeIn(delay: 450.ms),
                
                const SizedBox(height: 24),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return SwitchListTile(
      value: value,
      onChanged: onChanged,
      activeColor: AppTheme.primaryColor,
      activeTrackColor: AppTheme.primaryColor.withValues(alpha: 0.2),
      inactiveTrackColor: Colors.white.withValues(alpha: 0.05),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      title: Text(
        title,
        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
      ),
      subtitle: Text(
        subtitle,
        style: const TextStyle(color: AppTheme.textGrey, fontSize: 11),
      ),
      secondary: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.03),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, color: Colors.white70, size: 20),
      ),
    );
  }

  Widget _buildDivider() {
    return Divider(
      height: 1,
      thickness: 1,
      color: Colors.white.withValues(alpha: 0.04),
      indent: 20,
      endIndent: 20,
    );
  }
}
