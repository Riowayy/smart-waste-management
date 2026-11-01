<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WasteReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WasteReportController extends Controller
{
    public function show(Request $request, int $report): View
    {
        $wasteReport = $request->user()
            ->wasteReports()
            ->with(['category:id,name', 'statusHistories.changedBy:id,name'])
            ->findOrFail($report);

        return view('user.reports.show', ['report' => $wasteReport]);
    }
}
