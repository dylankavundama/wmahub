import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricService {
  final LocalAuthentication _auth = LocalAuthentication();

  Future<bool> canAuthenticate() async {
    try {
      final bool canAuthenticateWithBiometrics = await _auth.canCheckBiometrics;
      final bool isDeviceSupported = await _auth.isDeviceSupported();
      return canAuthenticateWithBiometrics || isDeviceSupported;
    } on PlatformException catch (_) {
      return false;
    }
  }

  Future<bool> authenticate() async {
    try {
      if (!(await canAuthenticate())) return true; // Let them pass if biometrics are not supported
      return await _auth.authenticate(
        localizedReason: "Veuillez vous authentifier pour accéder à vos revenus",
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: false, // fallback to device PIN/passcode if biometrics are not enrolled
        ),
      );
    } on PlatformException catch (_) {
      return false;
    }
  }
}
