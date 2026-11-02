<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $query = request()->user()->wasteReports();
        $summary = (clone $query)->selectRaw("COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")->first();
        $reports = $query->with('category:id,name,slug')->latest()->latest('id')->limit(5)->get();

        return view('user.dashboard', compact('reports', 'summary'));
    }
}
