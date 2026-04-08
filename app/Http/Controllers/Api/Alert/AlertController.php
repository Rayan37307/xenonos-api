<?php

namespace App\Http\Controllers\Api\Alert;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AlertController extends Controller
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $alerts = $this->alertService->getUserAlerts($status);

        return response()->json(['alerts' => $alerts]);
    }

    public function active()
    {
        $alerts = $this->alertService->getActiveAlerts();

        return response()->json(['alerts' => $alerts]);
    }

    public function show(string $id)
    {
        $alert = Alert::findOrFail($id);

        if ($alert->user_id !== auth()->id()) {
            throw ValidationException::withMessages(['message' => 'Unauthorized']);
        }

        return response()->json(['alert' => $alert]);
    }

    public function dismiss(string $id)
    {
        $alert = Alert::findOrFail($id);

        if ($alert->user_id !== auth()->id()) {
            throw ValidationException::withMessages(['message' => 'Unauthorized']);
        }

        $this->alertService->dismissAlert($alert);

        return response()->json(['message' => 'Alert dismissed']);
    }

    public function resolve(string $id)
    {
        $alert = Alert::findOrFail($id);

        if ($alert->user_id !== auth()->id()) {
            throw ValidationException::withMessages(['message' => 'Unauthorized']);
        }

        $this->alertService->resolveAlert($alert);

        return response()->json(['message' => 'Alert resolved']);
    }

    public function unreadCount()
    {
        $count = $this->alertService->getUnreadAlertCount();

        return response()->json(['count' => $count]);
    }
}
