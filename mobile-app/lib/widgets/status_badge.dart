import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  final String status;

  const StatusBadge({super.key, required this.status});

  static ({Color bg, Color fg, Color border, IconData icon, String label}) styleFor(
    String status,
  ) {
    switch (status) {
      case 'In Progress':
        return (
          bg: const Color(0xFFDBEAFE),
          fg: const Color(0xFF1E3A8A),
          border: const Color(0xFF3B82F6),
          icon: Icons.local_shipping_outlined,
          label: 'In Progress',
        );
      case 'Completed':
        return (
          bg: const Color(0xFFDCFCE7),
          fg: const Color(0xFF14532D),
          border: const Color(0xFF22C55E),
          icon: Icons.check_circle_outline,
          label: 'Completed',
        );
      case 'Rejected':
        return (
          bg: const Color(0xFFFEE2E2),
          fg: const Color(0xFF7F1D1D),
          border: const Color(0xFFEF4444),
          icon: Icons.cancel_outlined,
          label: 'Rejected',
        );
      case 'Pending':
      default:
        return (
          bg: const Color(0xFFFEF3C7),
          fg: const Color(0xFF78350F),
          border: const Color(0xFFF59E0B),
          icon: Icons.schedule_outlined,
          label: status.isEmpty ? 'Pending' : status,
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = styleFor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: s.bg,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: s.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(s.icon, size: 14, color: s.fg),
          const SizedBox(width: 5),
          Flexible(
            child: Text(
              s.label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: s.fg,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class WasteTypeBadge extends StatelessWidget {
  final String wasteType;
  final double? confidenceScore;
  final bool showConfidence;

  const WasteTypeBadge({
    super.key,
    required this.wasteType,
    this.confidenceScore,
    this.showConfidence = false,
  });

  static ({IconData icon, Color color}) metaFor(String wasteType) {
    switch (wasteType) {
      case 'Organic Waste':
        return (icon: Icons.eco_outlined, color: const Color(0xFF14532D));
      case 'Paper Waste':
        return (icon: Icons.description_outlined, color: const Color(0xFF78350F));
      case 'Plastic Waste':
      default:
        return (icon: Icons.recycling_outlined, color: const Color(0xFF075985));
    }
  }

  @override
  Widget build(BuildContext context) {
    final meta = metaFor(wasteType);
    final pct = (showConfidence && confidenceScore != null)
        ? ' • ${(confidenceScore! * 100).toStringAsFixed(1)}%'
        : '';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F7F4),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: const Color(0xFFB8C9BD)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(meta.icon, size: 14, color: meta.color),
          const SizedBox(width: 5),
          Flexible(
            child: Text(
              '$wasteType$pct',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: meta.color,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
