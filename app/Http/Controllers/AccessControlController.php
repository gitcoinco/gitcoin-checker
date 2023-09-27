<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccessControl;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class AccessControlController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $accessControls = AccessControl::all();
        return inertia('AccessControl/Index', ['accessControls' => $accessControls]);
    }

    public function upsert(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'eth_addr' => 'required|unique:access_controls',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            $notificationService = app(NotificationService::class);
            $notificationService->handleValidationErrors($validator);
            return redirect()->back()->withInput();
        }


        AccessControl::updateOrCreate(
            ['eth_addr' => $request->eth_addr],
            ['role' => $request->role]
        );

        $accessControls = AccessControl::all();

        $this->notificationService->success('Access Control Entry Created Successfully.');
        return redirect()->route('access-control.index', ['accessControls' => $accessControls]);
    }

    public function destroy(AccessControl $accessControl)
    {
        if ($accessControl->role === 'admin' && AccessControl::where('role', 'admin')->count() === 1) {
            $this->notificationService->error('Cannot delete the only admin.');
            return redirect()->route('access-control.index');
        }

        $accessControl->delete();

        $accessControls = AccessControl::all();
        $this->notificationService->success('Access Control Entry Deleted Successfully.');
        return redirect()->route('access-control.index', ['accessControls' => $accessControls]);
    }
}
