<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $query = PurchaseRequest::query()
            ->withCount('items')
            ->latest('updated_at');

        if (($user->role ?? null) === 'requester') {
            $query->where(function ($query) use ($user) {
                $query->where('requested_by', $user->id);

                if (! empty($user->department_name)) {
                    $query->orWhere('department_name', $user->department_name);
                }
            });
        }

        $purchaseRequests = $query
            ->limit(30)
            ->get();

        return view('purchasing.v2.dashboard', [
            'user' => $user,
            'purchaseRequests' => $purchaseRequests,
        ]);
    }
}
