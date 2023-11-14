<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\UserPreference;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function roundsSearch(Request $request)
    {
        if ($request->search) {
            $rounds = Round::where('name', 'like', '%' . $request->search . '%')->with('chain')->orderBy('round_start_time', 'desc')->limit(10)->get();
        } else {
            $rounds = [];
        }

        $userPreference = UserPreference::where('user_id', $request->user()->id)
            ->where('key', 'selectedApplicationRoundUuidList')
            ->first();
        $selectedRoundsUuid = $userPreference ? json_decode($userPreference->value, true) : [];
        $selectedRounds = Round::whereIn('uuid', $selectedRoundsUuid)->with('chain')->get();

        return [
            'rounds' => $rounds,
            'selectedRounds' => $selectedRounds,
        ];
    }

    public function selectedApplicationRoundType(Request $request)
    {
        // die('here');
        $selectedApplicationRoundType = $request->input('selectedApplicationRoundType', 'all');

        $userPreference = UserPreference::firstOrCreate([
            'user_id' => $request->user()->id,
            'key' => 'selectedApplicationRoundType',
        ]);
        $userPreference->value = json_encode($selectedApplicationRoundType);
        $userPreference->save();
    }

    public function roundToggle(Round $round)
    {
        $user = auth()->user();
        // die('here');
        $user = auth()->user();
        $userPreference = UserPreference::firstOrCreate([
            'user_id' => $user->id,
            'key' => 'selectedApplicationRoundUuidList',
        ], [
            'value' => json_encode([]),
        ]);

        $selectedApplicationRoundUuidList = json_decode($userPreference->value, true);

        // Toggle the round
        if (in_array($round->uuid, $selectedApplicationRoundUuidList)) {
            $selectedApplicationRoundUuidList = array_diff($selectedApplicationRoundUuidList, [$round->uuid]);
        } else {
            $selectedApplicationRoundUuidList[] = $round->uuid;
        }

        // Save
        $userPreference->value = json_encode($selectedApplicationRoundUuidList);
        $userPreference->save();

        $userPreference = UserPreference::where('user_id', $user->id)
            ->where('key', 'selectedApplicationRoundUuidList')
            ->first();
        $selectedRoundsUuid = $userPreference ? json_decode($userPreference->value, true) : [];
        $selectedRounds = Round::whereIn('uuid', $selectedRoundsUuid)->with('chain')->get();

        return [
            'selectedRounds' => $selectedRounds,
        ];
    }
}
