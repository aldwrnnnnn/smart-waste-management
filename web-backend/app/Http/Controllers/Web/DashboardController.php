<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function adminDashboard(): View
    {
        $stats = $this->buildStats(null);

        $pendingQueue = WasteReport::with('user:id,name,email')
            ->whereIn('status', ['Pending', 'In Progress'])
            ->latest()
            ->take(6)
            ->get();

        $recentReports = WasteReport::with('user:id,name,email')
            ->latest()
            ->take(8)
            ->get();

        $totalUsers = User::where('role', 'user')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $avgConfidence = (float) (WasteReport::whereNotNull('confidence_score')->avg('confidence_score') ?? 0);

        return view('dashboard.admin', [
            'stats' => $stats,
            'pendingQueue' => $pendingQueue,
            'recentReports' => $recentReports,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'avgConfidence' => round($avgConfidence * 100, 1),
        ]);
    }

    public function userDashboard(Request $request): View
    {
        $user = $request->user();
        $stats = $this->buildStats($user->id);

        $recentReports = WasteReport::where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.user', [
            'stats' => $stats,
            'recentReports' => $recentReports,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(?int $userId): array
    {
        $base = WasteReport::query();
        if ($userId !== null) {
            $base->where('user_id', $userId);
        }

        $statusMap = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $wasteMap = (clone $base)
            ->selectRaw('waste_type, COUNT(*) as total')
            ->groupBy('waste_type')
            ->pluck('total', 'waste_type');

        $pending = (int) ($statusMap['Pending'] ?? 0);
        $inProgress = (int) ($statusMap['In Progress'] ?? 0);
        $completed = (int) ($statusMap['Completed'] ?? 0);
        $rejected = (int) ($statusMap['Rejected'] ?? 0);
        $total = $pending + $inProgress + $completed + $rejected;

        $organic = (int) ($wasteMap['Organic Waste'] ?? 0);
        $plastic = (int) ($wasteMap['Plastic Waste'] ?? 0);
        $paper = (int) ($wasteMap['Paper Waste'] ?? 0);

        return [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'rejected' => $rejected,
            'organic' => $organic,
            'plastic' => $plastic,
            'paper' => $paper,
        ];
    }
}
