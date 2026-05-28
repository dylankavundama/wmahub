import 'dart:io';
import 'package:app_tracking_transparency/app_tracking_transparency.dart';

/// Requests ATT permission on iOS and returns the status.
Future<TrackingStatus> requestAppTrackingPermission() async {
  if (!Platform.isIOS) {
    return TrackingStatus.notSupported;
  }
  final status = await AppTrackingTransparency.trackingAuthorizationStatus;
  if (status == TrackingStatus.notDetermined) {
    // Show the ATT dialog to the user.
    return await AppTrackingTransparency.requestTrackingAuthorization();
  }
  return status;
}
