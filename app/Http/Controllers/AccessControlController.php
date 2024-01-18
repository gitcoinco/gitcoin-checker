<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccessControl;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AccessControlController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $accessControls = AccessControl::orderBy('id', 'desc')->get();

        // TODO::: For some or other weird reason, lazy loading with AccessControl::orderBy('id', 'desc')->with('user')->get(); is only loading the user relationship on the last row.  Do a little hack, as this isn't accessed very often.
        foreach ($accessControls as $accessControl) {
            $accessControl->user;
        }

        return inertia('AccessControl/Index', ['accessControls' => $accessControls]);
    }

    public function upsert(Request $request)
    {
        $this->authorize('update', AccessControl::class);

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
            ['eth_addr' => Str::lower($request->eth_addr)],
            ['role' => $request->role, 'name' => $request->name, 'email' => $request->email]
        );

        $accessControls = AccessControl::all();

        $this->notificationService->success('Access Control Entry Created Successfully.');
        return redirect()->route('access-control.index', ['accessControls' => $accessControls]);
    }

    public function destroy(AccessControl $accessControl)
    {
        if ($accessControl->role === 'admin' && AccessControl::where('role', 'admin')->count() <= 3) {
            $this->notificationService->error('You need a minimum of 3 administrators.');
            return redirect()->route('access-control.index');
        }

        $accessControl->delete();

        $accessControls = AccessControl::all();
        $this->notificationService->success('Access Control Entry Deleted Successfully.');
        return redirect()->route('access-control.index', ['accessControls' => $accessControls]);
    }
}
