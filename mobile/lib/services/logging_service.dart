import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'wordpress_service.dart';

/// Service de logging centralisé pour l'application WMAHub.
/// Envoie les erreurs critiques au serveur pour qu'elles apparaissent
/// dans le journal admin (site_stats.php) et soient envoyées par email.
class LoggingService {
  static const _timeout = Duration(seconds: 5);

  // Niveaux de log
  static const String levelInfo     = 'INFO';
  static const String levelWarning  = 'WARNING';
  static const String levelError    = 'ERROR';
  static const String levelCritical = 'CRITICAL';

  /// Log une erreur et l'envoie au serveur en arrière-plan.
  /// [level] : 'INFO' | 'WARNING' | 'ERROR' | 'CRITICAL'
  static void log(
    String message, {
    String level   = levelError,
    String file    = 'flutter',
    int line       = 0,
    String context = '',
  }) {
    // Toujours afficher en console debug
    if (kDebugMode) {
      debugPrint('[$level] $message${context.isNotEmpty ? ' | $context' : ''}');
    }

    // N'envoyer au serveur que les WARNING et plus en debug,
    // et tout en release
    if (!kDebugMode || level == levelCritical || level == levelError) {
      _sendToServer(
        message: message,
        level: level,
        file: file,
        line: line,
        context: context,
      );
    }
  }

  /// Raccourcis sémantiques
  static void info(String message, {String context = ''}) =>
      log(message, level: levelInfo, context: context);

  static void warning(String message, {String context = '', String file = 'flutter', int line = 0}) =>
      log(message, level: levelWarning, file: file, line: line, context: context);

  static void error(String message, {String context = '', String file = 'flutter', int line = 0}) =>
      log(message, level: levelError, file: file, line: line, context: context);

  static void critical(String message, {String context = '', String file = 'flutter', int line = 0}) =>
      log(message, level: levelCritical, file: file, line: line, context: context);

  /// Capture et log une exception Flutter.
  static void logException(
    Object exception,
    StackTrace? stack, {
    String context = '',
  }) {
    final message = exception.toString();
    final stackStr = stack?.toString().split('\n').take(5).join(' | ') ?? '';
    error(
      message,
      context: context.isNotEmpty ? '$context | Stack: $stackStr' : 'Stack: $stackStr',
      file: 'flutter_exception',
    );
  }

  /// Envoie silencieusement le log au serveur (fire & forget).
  static Future<void> _sendToServer({
    required String message,
    required String level,
    required String file,
    required int line,
    required String context,
  }) async {
    try {
      await http
          .post(
            Uri.parse('${WordPressService.apiBaseUrl}/log_mobile_error.php'),
            body: {
              'message': message,
              'level':   level,
              'file':    file,
              'line':    line.toString(),
              'context': context,
            },
          )
          .timeout(_timeout);
    } catch (_) {
      // Silencieux — ne jamais crasher à cause du logging
    }
  }
}
