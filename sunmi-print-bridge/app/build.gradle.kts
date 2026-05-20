plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "com.starcoffee.printbridge"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.starcoffee.printbridge"
        minSdk = 24
        targetSdk = 33
        versionCode = 2
        versionName = "0.2.0"
    }

    buildFeatures {
        aidl = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }
}
