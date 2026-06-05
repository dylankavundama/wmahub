import 'dart:math';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:workmanager/workmanager.dart';

class NotificationService {
  static final FlutterLocalNotificationsPlugin _notificationsPlugin = FlutterLocalNotificationsPlugin();

  static const String taskName = "com.ua.wmahub.reminderTask";

  // Liste de messages aléatoires pour l'utilisateur
  static final List<Map<String, String>> _reminderMessages = [
    {
      "title": "🚀 Prêt pour le prochain hit ?",
      "body": "N'oubliez pas de soumettre vos nouveaux projets pour distribution !"
    },
    {
      "title": "🎵 Votre musique mérite d'être entendue",
      "body": "Vérifiez vos statistiques et planifiez votre prochaine sortie sur WMA Hub."
    },
    {
      "title": "💰 Suivez vos revenus",
      "body": "De nouveaux rapports pourraient être disponibles. Jetez un œil à votre dashboard."
    },
    {
      "title": "💡 Conseil du jour",
      "body": "Une pochette de qualité augmente vos chances d'être playlisté !"
    },
    {
      "title": "🌍 WMA United Africa",
      "body": "Propulsez votre carrière au niveau supérieur dès aujourd'hui."
    },
    {
      "title": "🔥 Restez actif !",
      "body": "La régularité est la clé du succès. Qu'avez-vous préparé pour vos fans ?"
    }
  ];

  static Future<void> init() async {
    const AndroidInitializationSettings initializationSettingsAndroid = AndroidInitializationSettings('@mipmap/launcher_icon');
    const DarwinInitializationSettings initializationSettingsIOS = DarwinInitializationSettings();
    
    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsIOS,
    );

    await _notificationsPlugin.initialize(initializationSettings);
  }

  static Future<void> showRandomNotification() async {
    final random = Random();
    final message = _reminderMessages[random.nextInt(_reminderMessages.length)];

    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'reminder_channel',
      'Rappels WMA',
      channelDescription: 'Notifications de rappel automatique tous les 2 jours',
      importance: Importance.high,
      priority: Priority.high,
    );

    const NotificationDetails platformDetails = NotificationDetails(android: androidDetails);

    await _notificationsPlugin.show(
      0,
      message['title'],
      message['body'],
      platformDetails,
    );
  }

  static void scheduleReminder() {
    Workmanager().registerPeriodicTask(
      taskName,
      taskName,
      frequency: const Duration(hours: 48), // Toutes les 48 heures
      initialDelay: const Duration(minutes: 5), // Premier lancement après 5 min
      existingWorkPolicy: ExistingPeriodicWorkPolicy.keep,
    );
  }

  static Future<void> showMusicNotification({
    required String title,
    required String artist,
    required bool isPlaying,
  }) async {
    final AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'music_player_channel',
      'Lecteur de Musique',
      channelDescription: 'Contrôles de lecture audio en cours',
      importance: Importance.low,
      priority: Priority.low,
      ongoing: isPlaying,
      showWhen: false,
      onlyAlertOnce: true,
      autoCancel: false,
      styleInformation: const MediaStyleInformation(),
    );

    final NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
      iOS: const DarwinNotificationDetails(
        presentAlert: false,
        presentSound: false,
      ),
    );

    await _notificationsPlugin.show(
      999,
      isPlaying ? 'Lecture en cours' : 'Pause',
      '$title • $artist',
      platformDetails,
    );
  }

  static Future<void> cancelMusicNotification() async {
    await _notificationsPlugin.cancel(999);
  }
}

// Top-level function for Workmanager
@pragma('vm:entry-point')
void callbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    await NotificationService.init();
    await NotificationService.showRandomNotification();
    return Future.value(true);
  });
}
