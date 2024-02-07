<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetup;
use App\Models\NotificationSetupRound;
use App\Models\Round;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationSetupController extends Controller
{
    public function delete(NotificationSetup $notificationSetup)
    {
        $this->authorize('delete', $notificationSetup);

        $notificationSetup->delete();
        $user = User::find(auth()->id());
        $notificationSetups = $user->notificationSetups()->with(['notificationSetupRounds'])->paginate();

        return response()->json([
            'notificationSetups' => $notificationSetups,
            'message' => 'Notification setup deleted successfully'
        ]);
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'title' => 'required',
            // 'medium' => 'required',
            // 'details' => 'required',
            // 'include_applications' => 'required',
            // 'include_rounds' => 'required',
            'days_of_the_week' => 'required',
            'time_of_the_day' => 'required',
        ]);

        $notificationSetup = $request->uuid ? NotificationSetup::where('uuid', $request->uuid)->first() : new NotificationSetup();

        if ($notificationSetup->uuid) {
            $this->authorize('update', $notificationSetup);
        }

        $notificationSetup->user_id = auth()->id();
        $notificationSetup->title = $request->title;
        $notificationSetup->days_of_the_week = $request->days_of_the_week;
        $notificationSetup->time_of_the_day = $request->time_of_the_day;
        $notificationSetup->additional_emails = $request->additional_emails;
        $notificationSetup->save();

        // sync notification_setup_rounds
        $listOfNotificationSetupRoundIds = $request->notification_setup_rounds;
        $listOfNotificationSetupRoundsToAdd = [];
        $listOfNotificationSetupRoundsToRemove = [];

        foreach ($listOfNotificationSetupRoundIds as $notificationSetupRoundId) {
            $round = Round::where('id', $notificationSetupRoundId)->first();
            if ($round) {
                $listOfNotificationSetupRoundsToAdd[] = $round->id;
            }
        }

        $listOfNotificationSetupRoundsToRemove = $notificationSetup->notificationSetupRounds()->pluck('round_id')->diff($listOfNotificationSetupRoundsToAdd);

        foreach ($listOfNotificationSetupRoundsToRemove as $key => $notificationSetupRoundToRemove) {
            NotificationSetupRound::where('notification_setup_id', $notificationSetup->id)->where('round_id', $notificationSetupRoundToRemove)->first()->delete();
        }

        foreach ($listOfNotificationSetupRoundsToAdd as $notificationSetupRoundToAdd) {
            $notificationSetupRound = NotificationSetupRound::firstOrCreate(['notification_setup_id' => $notificationSetup->id, 'round_id' => $notificationSetupRoundToAdd]);
        }


        $user = User::find(auth()->id());
        $notificationSetups = $user->notificationSetups()->with(['notificationSetupRounds'])->paginate();
        return response()->json([
            'notificationSetups' => $notificationSetups,
            'message' => 'Notification setup saved successfully'
        ]);
    }


    public function index()
    {
        $user = User::find(auth()->id());


        $rounds = Round::where('created_at', '>=', now()->subYear())->orderBy('created_at', 'desc')->get();

        $notificationSetups = $user->notificationSetups()->with(['notificationSetupRounds'])->paginate();
        return Inertia::render('NotificationSetup/Index', [
            'notificationSetups' => $notificationSetups,
            'rounds' => $rounds
        ]);
    }
}
