class User {
  final String username;
  final String? fullName;
  final String role;
  final Map<String, dynamic>? extra;

  User({
    required this.username,
    this.fullName,
    required this.role,
    this.extra,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      username: json['username'] ?? '',
      fullName: json['full_name'],
      role: json['role'] ?? '',
      extra: json,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'username': username,
      'full_name': fullName,
      'role': role,
      ...?extra,
    };
  }
}

class AttendanceRecord {
  final int id;
  final String date;
  final String attendanceStatus;

  AttendanceRecord({
    required this.id,
    required this.date,
    required this.attendanceStatus,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceRecord(
      id: json['id'] ?? 0,
      date: json['date'] ?? '',
      attendanceStatus: json['attendance_status'] ?? 'present',
    );
  }
}
