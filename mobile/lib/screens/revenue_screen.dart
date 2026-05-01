import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';

class RevenueScreen extends StatefulWidget {
  final int userId;
  const RevenueScreen({super.key, required this.userId});

  @override
  State<RevenueScreen> createState() => _RevenueScreenState();
}

class _RevenueScreenState extends State<RevenueScreen> {
  Map<String, dynamic>? _revenueData;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchRevenues();
  }

  Future<void> _fetchRevenues() async {
    setState(() => _isLoading = true);
    try {
      final response = await http.get(
        Uri.parse("${WordPressService.apiBaseUrl}/get_artist_revenues.php?user_id=${widget.userId}"),
      );
      final data = json.decode(response.body);
      if (data['success'] == true) {
        setState(() => _revenueData = data['data']);
      }
    } catch (e) {
      debugPrint("Error: $e");
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('MES REVENUS', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        centerTitle: true,
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: _isLoading 
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : _buildBody(),
    );
  }

  Widget _buildBody() {
    return RefreshIndicator(
      onRefresh: _fetchRevenues,
      color: AppTheme.primaryColor,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildWalletCard(),
            const SizedBox(height: 32),
            const Text(
              'HISTORIQUE DES GAINS',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryColor, letterSpacing: 1.5),
            ),
            const SizedBox(height: 16),
            _buildTransactionList(),
          ],
        ),
      ),
    );
  }

  Widget _buildWalletCard() {
    double balanceUSD = double.parse((_revenueData?['balance_usd'] ?? 0).toString());
    double balanceCDF = double.parse((_revenueData?['balance_cdf'] ?? 0).toString());

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.orange.shade800, AppTheme.primaryColor],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          BoxShadow(color: AppTheme.primaryColor.withOpacity(0.3), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('SOLDE DISPONIBLE', style: TextStyle(color: Colors.white70, fontWeight: FontWeight.bold, fontSize: 11, letterSpacing: 1)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(10)),
                child: const Text('ACTIF', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Text(
            '${balanceUSD.toStringAsFixed(2)} \$',
            style: const TextStyle(color: Colors.white, fontSize: 44, fontWeight: FontWeight.w900, letterSpacing: -1),
          ),
          const SizedBox(height: 8),
          Text(
            '${balanceCDF.toStringAsFixed(0)} CDF',
            style: const TextStyle(color: Colors.white70, fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Veuillez faire votre demande de retrait sur le site web')),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: AppTheme.primaryColor,
              minimumSize: const Size(double.infinity, 54),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              elevation: 0,
            ),
            child: const Text('DEMANDER UN RETRAIT', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1)),
          ),
        ],
      ),
    ).animate().slideY(begin: 0.1, end: 0).fadeIn();
  }

  Widget _buildTransactionList() {
    List transactions = _revenueData?['transactions'] ?? [];
    
    if (transactions.isEmpty) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(40),
        decoration: BoxDecoration(color: AppTheme.cardColor, borderRadius: BorderRadius.circular(24)),
        child: const Column(
          children: [
            Icon(Icons.history_rounded, color: Colors.white10, size: 48),
            SizedBox(height: 16),
            Text('Aucun mouvement récent', style: TextStyle(color: Colors.white38, fontSize: 12)),
          ],
        ),
      );
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: transactions.length,
      separatorBuilder: (c, i) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final t = transactions[index];
        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppTheme.cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.white.withOpacity(0.05)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), shape: BoxShape.circle),
                child: const Icon(Icons.trending_up_rounded, color: Colors.green, size: 20),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(t['description'] ?? 'Revenu', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    Text(t['date'] ?? '', style: const TextStyle(color: AppTheme.textGrey, fontSize: 11)),
                  ],
                ),
              ),
              Text(
                '+${t['amount']} \$',
                style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 15),
              ),
            ],
          ),
        ).animate().fadeIn(delay: (index * 100).ms).slideX(begin: 0.1, end: 0);
      },
    );
  }
}
