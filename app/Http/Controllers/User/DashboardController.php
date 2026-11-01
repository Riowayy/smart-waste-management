<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $reports = request()->user()
            ->wasteReports()
            ->with('category')
            ->latest()
            ->paginate(10);

        return view('user.dashboard', compact('reports'));
    }
}
