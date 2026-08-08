import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:ct_mobile/providers/app_provider.dart';
import 'package:ct_mobile/utils/theme.dart';
import 'package:ct_mobile/utils/constants.dart';
import 'package:ct_mobile/models/user.dart';
import 'package:ct_mobile/widgets/screen_container.dart';
import 'package:ct_mobile/widgets/primary_button.dart';
import 'package:ct_mobile/widgets/empty_state.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  List<AttendanceRecord> _records = [];
  bool _isLoading = true;
  bool _isSubmitting = false;
  String _selectedStatus = Constants.attendanceStatus.first;
  String _selectedDate = DateFormat('yyyy-MM-dd').format(DateTime.now());

  Future<void> _loadAttendance() async {
    final provider = Provider.of<AppProvider>(context, listen: false);
    setState(() => _isLoading = true);

    try {
      final records = await provider.api.getAttendance();
      if (mounted) {
        setState(() {
          _records = records;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(e.toString().replaceAll('Exception: ', '')),
              backgroundColor: AppTheme.error,
            ),
          );
        }
      }
    }
  }

  Future<void> _markAttendance() async {
    final provider = Provider.of<AppProvider>(context, listen: false);
    setState(() => _isSubmitting = true);

    try {
      await provider.api.markAttendance(_selectedDate, _selectedStatus);
      await _loadAttendance();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Attendance marked successfully'),
            backgroundColor: AppTheme.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _loadAttendance();
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AppProvider>(context);
    final isAdmin = ['admin', 'teacher', 'staff'].contains(provider.user?.role);

    return ScreenContainer(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Attendance',
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: AppTheme.text,
            ),
          ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Mark for Today',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.text,
                    ),
                  ),
                  const SizedBox(height: 16),
                  RoleSelector(
                    options: Constants.attendanceStatus,
                    selected: _selectedStatus,
                    onSelected: (status) => setState(() => _selectedStatus = status),
                  ),
                  const SizedBox(height: 16),
                  PrimaryButton(
                    title: 'Submit Attendance',
                    onPressed: _markAttendance,
                    isLoading: _isSubmitting,
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Expanded(
            child: _isLoading && _records.isEmpty
                ? const LoadingOverlay()
                : _records.isEmpty
                    ? EmptyState(
                        icon: Icon(Icons.list_alt, size: 48, color: AppTheme.textSecondary),
                        title: 'No Records',
                        message: 'No attendance records found.',
                      )
                    : ListView.builder(
                        itemCount: _records.length,
                        itemBuilder: (context, index) {
                          final record = _records[index];
                          Color statusColor;
                          switch (record.attendanceStatus) {
                            case 'present':
                              statusColor = AppTheme.success;
                              break;
                            case 'absent':
                              statusColor = AppTheme.error;
                              break;
                            case 'late':
                              statusColor = AppTheme.warning;
                              break;
                            default:
                              statusColor = AppTheme.textSecondary;
                          }

                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              leading: Container(
                                width: 10,
                                height: 10,
                                decoration: BoxDecoration(
                                  color: statusColor,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              title: Text(
                                record.date,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                  color: AppTheme.text,
                                ),
                              ),
                              subtitle: Text(
                                record.attendanceStatus.toUpperCase(),
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: AppTheme.textSecondary,
                                ),
                              ),
                              trailing: isAdmin
                                  ? IconButton(
                                      icon: const Icon(Icons.delete_outline, color: AppTheme.error),
                                      onPressed: () {},
                                    )
                                  : null,
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
