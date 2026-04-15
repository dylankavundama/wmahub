import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';
import '../services/auth_service.dart';

class CreateProjectScreen extends StatefulWidget {
  const CreateProjectScreen({super.key});

  @override
  State<CreateProjectScreen> createState() => _CreateProjectScreenState();
}

class _CreateProjectScreenState extends State<CreateProjectScreen> {
  final _authService = AuthService();
  int _currentStep = 0;

  // Step 1: Personal Info
  final _fullNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _cityController = TextEditingController();

  // Step 2: Project Details
  final _titleController = TextEditingController();
  final _artistController = TextEditingController();
  String _projectType = 'Single';
  String _genre = 'Afrobeats';
  DateTime _selectedDate = DateTime.now().add(const Duration(days: 7));
  final _languagesController = TextEditingController();

  // Step 3: Content Details
  final _detailsController = TextEditingController();

  // Step 4: Media
  File? _coverFile;
  File? _audioFile;

  // Step 5: Pack & Legal
  String _selectedPack = 'Aucun';
  bool _isAuthorized = false;

  bool _isUploading = false;
  double _uploadProgress = 0;

  @override
  void initState() {
    super.initState();
    _loadUserInfo();
  }

  Future<void> _loadUserInfo() async {
    final user = await _authService.getCurrentUser();
    if (user != null) {
      setState(() {
        _fullNameController.text = user['full_name'] ?? '';
        _emailController.text = user['email'] ?? '';
        _artistController.text = user['display_name'] ?? '';
      });
    }
  }

  Future<void> _pickCover() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.image,
    );
    if (result != null) {
      setState(() {
        _coverFile = File(result.files.single.path!);
      });
    }
  }

  Future<void> _pickAudio() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.audio,
    );
    if (result != null) {
      setState(() {
        _audioFile = File(result.files.single.path!);
      });
    }
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime(2030),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.dark(
              primary: AppTheme.primaryColor,
              onPrimary: Colors.white,
              surface: AppTheme.cardColor,
              onSurface: Colors.white,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() => _selectedDate = picked);
    }
  }

  Future<void> _submitProject() async {
    if (!_isAuthorized) {
      _showSnackBar(
        'Veuillez accepter l\'autorisation de distribution',
        Colors.red,
      );
      return;
    }

    if (_coverFile == null || _audioFile == null) {
      _showSnackBar('Fichiers manquants (Pochette/Audio)', Colors.red);
      return;
    }

    setState(() {
      _isUploading = true;
      _uploadProgress = 0;
    });

    try {
      final user = await _authService.getCurrentUser();
      final userId = user?['id'] ?? 0;

      final uri = Uri.parse(
        "${WordPressService.apiBaseUrl}/submit_project.php",
      );
      var request = ProgressMultipartRequest(
        'POST',
        uri,
        onProgress: (bytes, total) {
          if (total != null && total > 0) {
            setState(() => _uploadProgress = bytes / total);
          }
        },
      );

      request.fields['user_id'] = userId.toString();
      request.fields['title'] = _titleController.text;
      request.fields['artist_name'] = _artistController.text;
      request.fields['full_name'] = _fullNameController.text;
      request.fields['email'] = _emailController.text;
      request.fields['phone'] = _phoneController.text;
      request.fields['city'] = _cityController.text;
      request.fields['languages'] = _languagesController.text;
      request.fields['details'] = _detailsController.text;
      request.fields['project_type'] = _projectType;
      request.fields['genre'] = _genre;
      request.fields['date_sortie'] = _selectedDate.toIso8601String().split(
        'T',
      )[0];
      request.fields['promo_pack'] = _selectedPack;
      request.fields['authorization'] = _isAuthorized ? '1' : '0';

      request.files.add(
        await http.MultipartFile.fromPath('cover', _coverFile!.path),
      );
      request.files.add(
        await http.MultipartFile.fromPath('audio', _audioFile!.path),
      );

      var response = await request.send();

      if (response.statusCode == 200) {
        if (mounted) {
          Navigator.pop(context);
          _showSnackBar('Projet soumis avec succès !', Colors.green);
        }
      } else {
        throw Exception('Erreur serveur: ${response.statusCode}');
      }
    } catch (e) {
      if (mounted) _showSnackBar('Erreur: $e', Colors.red);
    } finally {
      if (mounted) setState(() => _isUploading = false);
    }
  }

  void _showSnackBar(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Scaffold(
          appBar: AppBar(
            title: const Text(
              'NOUVELLE DISTRIBUTION',
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
            ),
            centerTitle: true,
            backgroundColor: Colors.transparent,
            elevation: 0,
          ),
          body: Column(
            children: [
              _buildStepIndicator(),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: _buildCurrentStepView(),
                ),
              ),
              _buildBottomActions(),
            ],
          ),
        ),
        if (_isUploading) _buildUploadOverlay(),
      ],
    );
  }

  Widget _buildStepIndicator() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          _buildStepDot(0, 'Perso'),
          _buildStepLine(0),
          _buildStepDot(1, 'Projet'),
          _buildStepLine(1),
          _buildStepDot(2, 'Infos'),
          _buildStepLine(2),
          _buildStepDot(3, 'Médias'),
          _buildStepLine(3),
          _buildStepDot(4, 'Pack'),
        ],
      ),
    );
  }

  Widget _buildStepDot(int index, String label) {
    bool isActive = _currentStep >= index;
    return Column(
      children: [
        AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          width: 20,
          height: 20,
          decoration: BoxDecoration(
            color: isActive ? AppTheme.primaryColor : Colors.white10,
            shape: BoxShape.circle,
            boxShadow: isActive
                ? [
                    BoxShadow(
                      color: AppTheme.primaryColor.withOpacity(0.4),
                      blurRadius: 8,
                    ),
                  ]
                : null,
          ),
          child: Center(
            child: isActive && _currentStep > index
                ? const Icon(Icons.check, size: 12, color: Colors.white)
                : Text(
                    '${index + 1}',
                    style: TextStyle(
                      color: isActive ? Colors.white : Colors.white38,
                      fontSize: 9,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: TextStyle(
            color: isActive ? Colors.white : Colors.white38,
            fontSize: 9,
          ),
        ),
      ],
    );
  }

  Widget _buildStepLine(int index) {
    bool isActive = _currentStep > index;
    return Container(
      width: 20,
      height: 2,
      margin: const EdgeInsets.only(left: 4, right: 4, bottom: 15),
      color: isActive ? AppTheme.primaryColor : Colors.white10,
    );
  }

  Widget _buildCurrentStepView() {
    switch (_currentStep) {
      case 0:
        return _buildStepPersonal();
      case 1:
        return _buildStepProject();
      case 2:
        return _buildStepContent();
      case 3:
        return _buildStepMedia();
      case 4:
        return _buildStepPack();
      default:
        return const SizedBox();
    }
  }

  Widget _buildStepPersonal() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('1. INFORMATIONS PERSONNELLES'),
        const SizedBox(height: 20),
        _buildTextField(
          _fullNameController,
          'Nom et Prénom *',
          Icons.badge_outlined,
        ),
        const SizedBox(height: 16),
        _buildTextField(
          _emailController,
          'Adresse E-mail *',
          Icons.alternate_email,
          type: TextInputType.emailAddress,
        ),
        const SizedBox(height: 16),
        _buildTextField(
          _phoneController,
          'WhatsApp (Numéro) *',
          Icons.phone_android_outlined,
          type: TextInputType.phone,
        ),
        const SizedBox(height: 16),
        _buildTextField(
          _cityController,
          'Ville *',
          Icons.location_city_outlined,
        ),
      ],
    ).animate().fadeIn();
  }

  Widget _buildStepProject() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('2. INFORMATIONS SUR LE PROJET'),
        const SizedBox(height: 20),
        _buildTextField(_titleController, 'Titre du Projet *', Icons.title),
        const SizedBox(height: 16),
        _buildTextField(
          _artistController,
          'Nom d\'Artiste *',
          Icons.mic_none_outlined,
        ),
        const SizedBox(height: 16),
        _buildProjectTypeDropdown(),
        const SizedBox(height: 16),
        _buildGenreDropdown(),
        const SizedBox(height: 16),
        _buildDateField(),
        const SizedBox(height: 16),
        _buildTextField(
          _languagesController,
          'Langues chantées *',
          Icons.language_outlined,
        ),
      ],
    ).animate().fadeIn();
  }

  Widget _buildStepContent() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('3. DÉTAILS DU PROJET'),
        const SizedBox(height: 20),
        _buildTextField(
          _detailsController,
          'Liste des titres / Détails *',
          Icons.format_list_bulleted_outlined,
          maxLines: 5,
        ),
      ],
    ).animate().fadeIn();
  }

  Widget _buildStepMedia() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('4. FICHIERS ET MÉDIAS'),
        const SizedBox(height: 20),
        _buildMediaPicker(
          'Pochette (JPG/PNG) *',
          _coverFile,
          _pickCover,
          Icons.image_outlined,
        ),
        const SizedBox(height: 24),
        _buildMediaPicker(
          'Fichier Audio (MP3/WAV) *',
          _audioFile,
          _pickAudio,
          Icons.audio_file_outlined,
        ),
      ],
    ).animate().fadeIn();
  }

  Widget _buildStepPack() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('5. PACK PROMO & LÉGAL'),
        const SizedBox(height: 20),
        _buildPackOption('Aucun', 'Distribution basique gratuite', '\$0'),
        const SizedBox(height: 12),
        _buildPackOption(
          'Starter',
          'Distribution sur toutes les plateformes',
          '\$15',
        ),
        const SizedBox(height: 12),
        _buildPackOption('Pro', 'Distribution + Promo réseaux sociaux', '\$35'),
        const SizedBox(height: 12),
        _buildPackOption('Premium', 'Promo complète + Clip YouTube', '\$75'),
        const SizedBox(height: 32),
        _buildAuthorizationCheckbox(),
      ],
    ).animate().fadeIn();
  }

  Widget _buildSectionTitle(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.bold,
        color: AppTheme.primaryColor,
        letterSpacing: 1.5,
      ),
    );
  }

  Widget _buildTextField(
    TextEditingController controller,
    String label,
    IconData icon, {
    TextInputType type = TextInputType.text,
    int maxLines = 1,
  }) {
    return TextField(
      controller: controller,
      keyboardType: type,
      maxLines: maxLines,
      style: const TextStyle(color: Colors.white, fontSize: 14),
      decoration: _inputDecoration(label, icon),
    );
  }

  Widget _buildDateField() {
    return InkWell(
      onTap: _selectDate,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            const Icon(
              Icons.calendar_today_outlined,
              color: AppTheme.primaryColor,
              size: 20,
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Date de Sortie Souhaitée *',
                  style: TextStyle(color: AppTheme.textGrey, fontSize: 10),
                ),
                Text(
                  _selectedDate.toIso8601String().split('T')[0],
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProjectTypeDropdown() {
    return DropdownButtonFormField<String>(
      value: _projectType,
      dropdownColor: AppTheme.cardColor,
      style: const TextStyle(color: Colors.white, fontSize: 14),
      decoration: _inputDecoration('Type de Projet *', Icons.category_outlined),
      items: [
        'Single',
        'Album',
        'EP',
      ].map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
      onChanged: (v) => setState(() => _projectType = v!),
    );
  }

  Widget _buildGenreDropdown() {
    return DropdownButtonFormField<String>(
      value: _genre,
      dropdownColor: AppTheme.cardColor,
      style: const TextStyle(color: Colors.white, fontSize: 14),
      decoration: _inputDecoration(
        'Genre Musical *',
        Icons.music_note_outlined,
      ),
      items: [
        'Afrobeats',
        'Hip-Hop',
        'Pop',
        'R&B',
        'Gospel',
        'Autre',
      ].map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
      onChanged: (v) => setState(() => _genre = v!),
    );
  }

  InputDecoration _inputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(color: AppTheme.textGrey, fontSize: 12),
      prefixIcon: Icon(icon, color: AppTheme.primaryColor, size: 20),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(20),
        borderSide: const BorderSide(color: Colors.white10),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(20),
        borderSide: const BorderSide(color: AppTheme.primaryColor),
      ),
      filled: true,
      fillColor: AppTheme.cardColor,
    );
  }

  Widget _buildMediaPicker(
    String label,
    File? file,
    VoidCallback onTap,
    IconData icon,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: AppTheme.textGrey,
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: AppTheme.cardColor,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: file != null ? AppTheme.primaryColor : Colors.white10,
                width: 2,
              ),
            ),
            child: Column(
              children: [
                Icon(
                  icon,
                  size: 32,
                  color: file != null ? AppTheme.primaryColor : Colors.white38,
                ),
                const SizedBox(height: 12),
                Text(
                  file != null
                      ? file.path.split('/').last
                      : 'Cliquez pour choisir',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: file != null ? Colors.white : Colors.white38,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildPackOption(String name, String desc, String price) {
    bool isSelected = _selectedPack == name;
    return InkWell(
      onTap: () => setState(() => _selectedPack = name),
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isSelected
              ? AppTheme.primaryColor.withOpacity(0.1)
              : AppTheme.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? AppTheme.primaryColor : Colors.white10,
            width: 2,
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    style: TextStyle(
                      color: isSelected ? AppTheme.primaryColor : Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    desc,
                    style: const TextStyle(
                      color: AppTheme.textGrey,
                      fontSize: 10,
                    ),
                  ),
                ],
              ),
            ),
            Text(
              price,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 20,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAuthorizationCheckbox() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.primaryColor.withOpacity(0.05),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.primaryColor.withOpacity(0.2)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Checkbox(
            value: _isAuthorized,
            onChanged: (v) => setState(() => _isAuthorized = v!),
            activeColor: AppTheme.primaryColor,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(4),
            ),
          ),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Autorisation de Distribution *',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'J\'autorise WMA HUB à procéder à la distribution de mon projet sur les plateformes numériques.',
                  style: TextStyle(color: AppTheme.textGrey, fontSize: 10),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActions() {
    bool isLast = _currentStep == 4;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: Colors.black26,
        border: Border(top: BorderSide(color: Colors.white10)),
      ),
      child: Row(
        children: [
          if (_currentStep > 0)
            Expanded(
              child: TextButton(
                onPressed: _isUploading
                    ? null
                    : () => setState(() => _currentStep--),
                child: const Text(
                  'RETOUR',
                  style: TextStyle(
                    color: AppTheme.textGrey,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          const SizedBox(width: 16),
          Expanded(
            flex: 2,
            child: ElevatedButton(
              onPressed: _isUploading
                  ? null
                  : (isLast
                        ? _submitProject
                        : () => setState(() => _currentStep++)),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryColor,
                padding: const EdgeInsets.symmetric(vertical: 18),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
              ),
              child: Text(
                isLast ? 'SOUMETTRE LE PROJET' : 'CONTINUER',
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUploadOverlay() {
    return Material(
      type: MaterialType.transparency,
      child: Container(
        color: Colors.black.withOpacity(0.85),
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 40),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 80,
                  height: 80,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const CircularProgressIndicator(
                    color: AppTheme.primaryColor,
                    strokeWidth: 3,
                  ),
                ).animate().scale(duration: 400.ms),
                const SizedBox(height: 32),
                const Text(
                  'ENVOI EN COURS...',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 2,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'Ne fermez pas l\'application pendant le transfert',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.5),
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 48),
                Stack(
                  children: [
                    Container(
                      height: 8,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white10,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      height: 8,
                      width:
                          MediaQuery.of(context).size.width *
                          0.8 *
                          _uploadProgress,
                      decoration: BoxDecoration(
                        color: AppTheme.primaryColor,
                        borderRadius: BorderRadius.circular(4),
                        boxShadow: [
                          BoxShadow(
                            color: AppTheme.primaryColor.withOpacity(0.5),
                            blurRadius: 10,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  '${(_uploadProgress * 100).toInt()}%',
                  style: const TextStyle(
                    color: AppTheme.primaryColor,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class ProgressMultipartRequest extends http.MultipartRequest {
  final Function(int bytes, int? total) onProgress;
  ProgressMultipartRequest(String method, Uri url, {required this.onProgress})
    : super(method, url);
  @override
  http.ByteStream finalize() {
    final byteStream = super.finalize();
    final total = contentLength;
    int bytes = 0;
    final transformer = StreamTransformer.fromHandlers(
      handleData: (List<int> data, EventSink<List<int>> sink) {
        bytes += data.length;
        onProgress(bytes, total);
        sink.add(data);
      },
    );
    return http.ByteStream(byteStream.transform(transformer));
  }
}
