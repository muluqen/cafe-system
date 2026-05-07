# Cafe System Mobile App (Flutter)

Flutter mobile application for the Cafe System.

## Features

* User authentication
* View restaurants
* Browse menu items
* Place orders
* Manage profile

## Setup

```bash
flutter pub get
flutter run
```

## API Configuration

Update backend API URL inside your constants/service file:

```dart
const baseUrl = 'http://10.0.2.2:8000/api';
```

For Android emulator:

* `10.0.2.2` = localhost

For real device:

* use your computer IP address

Example:

```dart
const baseUrl = 'http://192.168.1.5:8000/api';
```

## Project Structure

* lib/
* android/
* ios/


## Notes

Laravel backend must be running before launching app.
