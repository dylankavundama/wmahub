import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';
import 'distribution_screen.dart';
import 'project_detail_screen.dart';
import 'revenue_screen.dart';
import 'notification_screen.dart';
import 'admin_accounting_screen.dart';
import 'contract_screen.dart';
import '../services/wordpress_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _authService = AuthService();
  Map<String, dynamic>? _currentUser;
  Map<String, dynamic>? _dashboardData;
  bool _isLoading = true;
  bool _isDashboardLoading = false;

  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final user = await _authService.getCurrentUser();
    if (mounted) {
      setState(() {
        _currentUser = user;
        _isLoading = false;
      });
      if (user != null) {
        if (user['role'] == 'administrator') {
          // Redirection immédiate pour l'admin
          WidgetsBinding.instance.addPostFrameCallback((_) {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const AdminAccountingScreen()),
            );
          });
        } else {
          _fetchDashboardData();
        }
      }
    }
  }

  Future<void> _fetchDashboardData() async {
    if (_currentUser == null) return;
    setState(() => _isDashboardLoading = true);
    try {
      final userId = _currentUser!['id'];
      final response = await http
          .get(
            Uri.parse(
              "${WordPressService.apiBaseUrl}/get_artist_dashboard.php?user_id=$userId",
            ),
          )
          .timeout(const Duration(seconds: 10));

      final data = json.decode(response.body);
      if (mounted && data['success'] == true) {
        setState(() {
          _dashboardData = data['data'];
          _isDashboardLoading = false;
        });
      }
    } catch (e) {
      debugPrint("Error fetching dashboard: $e");
      if (mounted) setState(() => _isDashboardLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Vérification de sécurité/redirection à chaque build si on est admin
    if (_currentUser != null && _currentUser!['role'] == 'administrator') {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const AdminAccountingScreen()),
        );
      });
      return const Scaffold(body: Center(child: CircularProgressIndicator(color: AppTheme.primaryColor)));
    }

    if (_isLoading) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: AppTheme.primaryColor),
        ),
      );
    }

    return Scaffold(
      appBar: _currentUser == null
          ? null
          : AppBar(
              title: const Text(
                'Dashboard Artiste',
                style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1),
              ),
              backgroundColor: Colors.transparent,
              elevation: 0,
              actions: [
                IconButton(
                  icon: const Icon(
                    Icons.refresh_rounded,
                    color: AppTheme.primaryColor,
                  ),
                  onPressed: _fetchDashboardData,
                ),
                Stack(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.notifications_none_rounded, color: Colors.white),
                      onPressed: () {
                        if (_currentUser != null) {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) {
                                final rawId = _currentUser!['id'];
                                final intId = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
                                return NotificationScreen(userId: intId);
                              },
                            ),
                          ).then((_) => _fetchDashboardData()); // Refresh count on return
                        }
                      },
                    ),
                    if (_dashboardData != null && _dashboardData!['notification_count'] != null)
                      Builder(
                        builder: (context) {
                          final rawCount = _dashboardData!['notification_count'];
                          final count = rawCount is int ? rawCount : int.tryParse(rawCount.toString()) ?? 0;
                          
                          if (count <= 0) return const SizedBox.shrink();
                          
                          return Positioned(
                            right: 8,
                            top: 8,
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                              constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                              child: Text(
                                count > 9 ? '9+' : count.toString(),
                                style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ).animate().scale();
                        },
                      ),
                  ],
                ),
                IconButton(
                  icon: const Icon(
                    Icons.logout_rounded,
                    color: Colors.redAccent,
                  ),
                  onPressed: () async {
                    await _authService.logout();
                    _checkAuth();
                  },
                ),
              ],
            ),
      body: _currentUser == null
          ? LoginScreen(onLoginSuccess: _checkAuth)
          : RefreshIndicator(
              onRefresh: _fetchDashboardData,
              color: AppTheme.primaryColor,
              child: _buildArtistDashboard(),
            ),
    );
  }

  Widget _buildArtistDashboard() {
    final stats = _dashboardData?['stats'];
    final recent = _dashboardData?['recent_projects'] as List? ?? [];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      physics: const AlwaysScrollableScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // User Header
          Row(
            children: [
              const CircleAvatar(
                radius: 40,
                backgroundColor: AppTheme.primaryColor,
                child: Icon(Icons.person, size: 40, color: Colors.white),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _currentUser?['name'] ?? 'Artiste WMA',
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      _currentUser?['email'] ?? '',
                      style: const TextStyle(color: AppTheme.textGrey),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 32),

          // CTA: Nouvelle Sortie
          ElevatedButton.icon(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const DistributionScreen(),
                ),
              );
            },
            icon: const Icon(Icons.rocket_launch, color: Colors.white),
            label: const Text(
              'NOUVELLE SORTIE',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
              foregroundColor: Colors.white,
              minimumSize: const Size(double.infinity, 56),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ).animate().fadeIn().scale(),

          const SizedBox(height: 40),

          // Quick Access Stats
          const Text(
            "VUE D'ENSEMBLE",
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: AppTheme.primaryColor,
              letterSpacing: 1.5,
            ),
          ),
          const SizedBox(height: 16),
          _buildStatsGrid(stats),

          const SizedBox(height: 40),

          // Recent Releases
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                "SORTIES RÉCENTES",
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.primaryColor,
                  letterSpacing: 1.5,
                ),
              ),
              TextButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const DistributionScreen(),
                    ),
                  );
                },
                child: const Text(
                  'VOIR TOUT',
                  style: TextStyle(fontSize: 11, color: AppTheme.textGrey),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (recent.isEmpty && !_isDashboardLoading)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: Text(
                  "Aucun projet récent",
                  style: TextStyle(color: AppTheme.textGrey),
                ),
              ),
            )
          else
            ...recent.map((p) => _buildRecentProjectItem(p)).toList(),

          const SizedBox(height: 40),

          // Actions
          const Text(
            "GESTION & OUTILS",
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: AppTheme.primaryColor,
              letterSpacing: 1.5,
            ),
          ),
          const SizedBox(height: 16),
          _buildDashboardCard(
            Icons.music_note_outlined,
            'Catalogue Musical',
            'Gérez votre discographie complète',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const DistributionScreen(),
                ),
              );
            },
          ),
          _buildDashboardCard(
            Icons.account_balance_wallet_outlined,
            'Mes Revenus',
            'Consultez vos rapports financiers',
            onTap: () {
              if (_currentUser != null) {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) {
                      final rawId = _currentUser!['id'];
                      final intId = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
                      return RevenueScreen(userId: intId);
                    },
                  ),
                );
              }
            },
          ),
          _buildDashboardCard(
            Icons.notifications_outlined,
            'Notifications',
            'Suivez vos alertes et mises à jour',
            onTap: () {
              if (_currentUser != null) {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) {
                      final rawId = _currentUser!['id'];
                      final intId = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
                      return NotificationScreen(userId: intId);
                    },
                  ),
                );
              }
            },
          ),
          _buildDashboardCard(
            Icons.description_outlined,
            'Contrats',
            'Gérez vos accords de distribution',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) {
                    final rawId = _currentUser!['id'];
                    final intId = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
                    return ContractScreen(
                      userId: intId,
                      onSigned: () {
                        // Rafraîchir les données si le contrat vient d'être signé
                        _fetchDashboardData();
                      },
                    );
                  },
                ),
              );
            },
          ),
          _buildDashboardCard(
            Icons.auto_awesome_outlined,
            'Assistant d\'écriture',
            'Aide à la création (IA)',
            isPro: true,
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('L\'Assistant d\'écriture IA sera disponible dans une prochaine mise à jour.'),
                  backgroundColor: AppTheme.primaryColor,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
          ),
          _buildDashboardCard(
            Icons.privacy_tip_outlined,
            'Politique de Confidentialité',
            'Consultez nos engagements légaux',
            onTap: () => _launchURL('https://wmahub.com/privacy.php'),
          ),
          const SizedBox(height: 24),
          TextButton.icon(
            onPressed: _showDeleteAccountDialog,
            icon: const Icon(Icons.delete_forever, color: Colors.redAccent, size: 18),
            label: const Text(
              'SUPPRIMER MON COMPTE',
              style: TextStyle(color: Colors.redAccent, fontSize: 11, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  void _showDeleteAccountDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.cardColor,
        title: const Text('Supprimer le compte ?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        content: const Text(
          'Cette action est irréversible. Vos données de distribution et votre accès seront désactivés conformément aux règles de l\'App Store.',
          style: TextStyle(color: AppTheme.textGrey, fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('ANNULER', style: TextStyle(color: AppTheme.textGrey)),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _deleteAccount();
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            child: const Text('CONFIRMER LA SUPPRESSION', style: TextStyle(fontSize: 11)),
          ),
        ],
      ),
    );
  }

  Future<void> _deleteAccount() async {
    if (_currentUser == null) return;
    try {
      final response = await http.post(
        Uri.parse("${WordPressService.apiBaseUrl}/delete_account.php"),
        body: {'user_id': _currentUser!['id'].toString()},
      );
      
      if (mounted) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          await _authService.logout();
          _checkAuth();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message']), backgroundColor: Colors.green),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Erreur lors de la suppression'), backgroundColor: Colors.red),
        );
      }
    }
  }

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

  Widget _buildRecentProjectItem(Map<String, dynamic> project) {
    final status = project['status'] ?? 'en_attente';
    Color statusColor = AppTheme.primaryColor;
    if (status == 'distribue') statusColor = Colors.green;
    if (status == 'en_preparation') statusColor = Colors.blue;

    return InkWell(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ProjectDetailScreen(project: project),
          ),
        );
      },
      borderRadius: BorderRadius.circular(20),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
            decoration: BoxDecoration(
              color: Colors.white10,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.album, color: AppTheme.textGrey),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  project['title'] ?? 'Sans titre',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        status.replaceAll('_', ' ').toUpperCase(),
                        style: TextStyle(
                          color: statusColor,
                          fontSize: 8,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Icon(
                      Icons.visibility,
                      size: 10,
                      color: AppTheme.textGrey,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      '${project['streams'] ?? 0}',
                      style: const TextStyle(
                        fontSize: 10,
                        color: AppTheme.textGrey,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right, color: AppTheme.textGrey, size: 20),
        ],
      ),
    ),
  );
}

  Widget _buildStatsGrid(Map<String, dynamic>? stats) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 16,
      crossAxisSpacing: 16,
      childAspectRatio: 1.5,
      children: [
        _buildStatTile(
          'Projets',
          '${stats?['total_projects'] ?? 0}',
          Icons.album_outlined,
        ),
        _buildStatTile(
          'Vues Totales',
          '${stats?['total_streams'] ?? 0}',
          Icons.visibility_outlined,
        ),
        _buildStatTile(
          'Distribués',
          '${stats?['distributed_count'] ?? 0}',
          Icons.cloud_done_outlined,
        ),
        _buildStatTile(
          'Revenus (\$)',
          '${stats?['total_revenue'] ?? 0}',
          Icons.account_balance_wallet_outlined,
        ),
      ],
    );
  }

  Widget _buildStatTile(String label, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 20, color: AppTheme.primaryColor),
          const SizedBox(height: 8),
          Flexible(
            child: Text(
              value,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ),
          Text(
            label,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11, color: AppTheme.textGrey),
          ),
        ],
      ),
    );
  }

  Widget _buildDashboardCard(
    IconData icon,
    String title,
    String subtitle, {
    VoidCallback? onTap,
    bool isPro = false,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white10),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppTheme.primaryColor.withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: AppTheme.primaryColor, size: 20),
        ),
        title: Row(
          children: [
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
            if (isPro) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.orange.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: const Text(
                  'PRO',
                  style: TextStyle(
                    color: Colors.orange,
                    fontSize: 8,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ],
        ),
        subtitle: Text(
          subtitle,
          style: const TextStyle(fontSize: 11, color: AppTheme.textGrey),
        ),
        trailing: const Icon(
          Icons.chevron_right,
          color: AppTheme.textGrey,
          size: 20,
        ),
        onTap: onTap ?? () {},
      ),
    );
  }
}
