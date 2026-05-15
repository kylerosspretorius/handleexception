<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $stats = [
            'total'    => $user->invoices()->count(),
            'revenue'  => $user->invoices()->whereMonth('invoice_date', now()->month)->sum('total'),
            'currency' => $user->default_currency ?? 'USD',
            'unpaid'   => $user->invoices()->whereIn('status', ['draft', 'sent'])->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }
}
