# Flutter wrapper
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.**  { *; }
-keep class io.flutter.util.**  { *; }
-keep class io.flutter.view.**  { *; }
-keep class io.flutter.**  { *; }
-keep class io.flutter.plugins.**  { *; }

# Firebase Auth / Google Sign-In (Credential Manager)
-keep class com.google.android.gms.auth.api.signin.** { *; }
-keep class com.google.android.gms.common.api.** { *; }
-keep class com.google.android.gms.tasks.** { *; }
-keep class com.google.firebase.auth.** { *; }

# Workmanager
-keep class com.google.common.util.concurrent.ListenableFuture { *; }
-keep class androidx.work.** { *; }

# Google Play Core (Flutter deferred components)
-dontwarn com.google.android.play.core.**
-keep class com.google.android.play.core.** { *; }

# OkHttp (used by http package)
-dontwarn okhttp3.**
-keep class okhttp3.** { *; }

# Audioplayers
-keep class xyz.luan.audioplayers.** { *; }

# File Picker
-keep class com.mr.flutter.plugin.filepicker.** { *; }

# Share Plus
-keep class dev.fluttercommunity.plus.share.** { *; }

# Connectivity Plus
-keep class dev.fluttercommunity.plus.connectivity.** { *; }

# Cached Network Image / Glide
-keep public class * implements com.bumptech.glide.module.GlideModule
-keep class * extends com.bumptech.glide.module.AppGlideModule { *; }

# Keep annotations
-keepattributes *Annotation*
-keepattributes Signature
-keepattributes Exceptions
