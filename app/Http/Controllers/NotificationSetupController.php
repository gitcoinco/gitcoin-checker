<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\NotificationLogApplications;
use App\Models\NotificationSetup;
use App\Models\NotificationSetupRound;
use App\Models\Round;
use App\Models\RoundApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Snowfire\Beautymail\Beautymail;

class NotificationSetupController extends Controller
{

    public function sendNotifications()
    {
        // Find all the notifications that should be sent out now

        $notificationSetups = NotificationSetup::where('time_type', 'specific')->where('days_of_the_week', 'like', '%' . date('l') . '%')->where('time_of_the_day', 'like', '%' . now()->format('H:i') . ':00%')->get();
        $notificationSetups = $notificationSetups->merge(NotificationSetup::where('time_type', 'minute')->where('days_of_the_week', 'like', '%' . date('l') . '%')->get());

        // Check hourly sends
        if (now()->format('i') == '00') {
            $notificationSetups = $notificationSetups->merge(NotificationSetup::where('time_type', 'hour')->where('days_of_the_week', 'like', '%' . date('l') . '%')->get());
        }

        foreach ($notificationSetups as $notificationSetup) {
            // Find all the applications that should be included in the notification
            $rounds = $notificationSetup->notificationSetupRounds()->pluck('round_id');

            $ignoreAlreadyCommunicatedApplications = $notificationSetup->notificationLogApplications()->pluck('application_id');
            $applications = RoundApplication::whereIn('round_id', $rounds)
                ->whereNotIn('id', $ignoreAlreadyCommunicatedApplications)
                ->where('created_at', '>=', $notificationSetup->created_at)
                ->orderBy('created_at', 'asc')
                ->limit($notificationSetup->nr_summaries_per_email)
                ->where('status', 'PENDING')
                ->get();

            // Exclude any applications that have already been sent for this user
            $applications = $applications->whereNotIn('id', $notificationSetup->notificationLogApplications()->pluck('application_id'));

            if ($applications->count() === 0) {
                echo 'No applications to send for ' . $notificationSetup->title . PHP_EOL;
                continue;
            }

            $notificationLog = new NotificationLog();
            $notificationLog->notification_setup_id = $notificationSetup->id;
            $notificationLog->subject = $notificationSetup->title;
            $notificationLog->message = 'This is a test message';
            $notificationLog->save();

            foreach ($applications as $application) {
                $notificationLogApplication = new NotificationLogApplications();
                $notificationLogApplication->notification_log_id = $notificationLog->id;
                $notificationLogApplication->notification_setup_id = $notificationSetup->id;
                $notificationLogApplication->user_id = $notificationSetup->user_id;
                $notificationLogApplication->application_id = $application->id;
                $notificationLogApplication->save();
            }

            // Send the notification
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.notification', [
                'notificationLog' => $notificationLog,
            ], function ($message) use ($notificationLog) {
                $user = $notificationLog->setup->user;

                $message
                    ->from('noreply@checker.gitcoin.co')
                    ->to($user->email, $user->firstname . ' ' . $user->lastname)
                    ->subject($notificationLog->subject);
            });

            // Send to the additional emails
            if ($notificationSetup->additional_emails) {
                //                $emails = json_decode($notificationSetup->additional_emails, true);

                foreach ($notificationSetup->additional_emails as $key => $email) {
                    $beautymail->send('emails.notification', [
                        'notificationLog' => $notificationLog,
                    ], function ($message) use ($notificationLog, $email) {
                        $message
                            ->from('noreply@checker.gitcoin.co')
                            ->to($email)
                            ->subject($notificationLog->subject);
                    });
                }
            }
        }
    }


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
            'email_subject' => 'required',
            // 'medium' => 'required',
            // 'details' => 'required',
            // 'include_applications' => 'required',
            // 'include_rounds' => 'required',
            // 'days_of_the_week' => 'required',
            'time_type' => 'required',
            'time_of_the_day' => 'required',
        ]);

        $notificationSetup = $request->uuid ? NotificationSetup::where('uuid', $request->uuid)->first() : new NotificationSetup();

        if ($notificationSetup->uuid) {
            $this->authorize('update', $notificationSetup);
        }

        $notificationSetup->user_id = auth()->id();
        $notificationSetup->title = $request->title;
        $notificationSetup->email_subject = $request->email_subject;
        $notificationSetup->days_of_the_week = $request->days_of_the_week;
        $notificationSetup->time_type = $request->time_type;
        $notificationSetup->time_of_the_day = $request->time_of_the_day;
        $notificationSetup->additional_emails = $request->additional_emails;
        $notificationSetup->nr_summaries_per_email = $request->nr_summaries_per_email;
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

        $notificationSetups = $user->notificationSetups()->with(['notificationSetupRounds', 'notificationLogs' => function ($query) {
            $query->selectRaw('notification_setup_id, count(*) as count')->groupBy('notification_setup_id');
        }])->paginate();


        return Inertia::render('NotificationSetup/Index', [
            'notificationSetups' => $notificationSetups,
            'rounds' => $rounds
        ]);
    }
}
