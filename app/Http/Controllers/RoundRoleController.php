<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\RoundRole;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoundRoleController extends Controller
{

    private $notificationService;


    public function __construct()
    {
        $this->notificationService = app(NotificationService::class);
    }

    public function show(Round $round)
    {
        $round->load('chain');
        $roundRoles = $round->roundRoles()->get();

        return Inertia::render('Round/RoundRole/Show', [
            'round' => $round,
            'roundRoles' => $roundRoles,
        ]);
    }

    public function destroy(RoundRole $roundRole)
    {
        $this->authorize('canDeleteRoundRole', $roundRole);

        $round = $roundRole->round;

        if ($roundRole->role == 'MANAGER') {
            $this->notificationService->error('Cannot delete manager role');
            return back();
        }

        $roundRole->delete();

        $this->notificationService->success('Round Role Deleted');

        $round->load('chain');
        $roundRoles = $round->roundRoles()->get();

        return Inertia::render('Round/RoundRole/Show', [
            'round' => $round,
            'roundRoles' => $roundRoles,
        ]);
    }

    public function upsert(Round $round)
    {
        $this->authorize('canEditRoundRole', $round);

        $request = request();

        $request->validate([
            'role' => 'required|string',
            'address' => 'required|string',
        ]);

        $roundRole = RoundRole::where('round_id', $round->id)->where('address', $request->address)->first();
        if ($roundRole) {
            $this->notificationService->error('Address already exists for this round');
        } else {
            $roundRole = new RoundRole();
            $roundRole->role = $request->role;
            $roundRole->address = $request->address;
            $roundRole->round_id = $round->id;
            $roundRole->save();
            $this->notificationService->success('Round Role Created/Updated');
        }


        $round->load('chain');
        $roundRoles = $round->roundRoles()->get();

        return Inertia::render('Round/RoundRole/Show', [
            'round' => $round,
            'roundRoles' => $roundRoles,
        ]);
    }
}
