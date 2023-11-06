<?php

namespace App\Http\Controllers;

use App\Models\AccessControl;
use App\Models\Chain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\NotificationService;

class ChainController extends Controller
{
    private $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $chains = Chain::orderBy('chain_id', 'asc')->get();

        return Inertia::render('Chain/Index', [
            'chains' => $chains
        ]);
    }

    public function updateAll(Request $request)
    {
        // who can update
        $this->authorize('update', AccessControl::class);

        $chainData = $request->chains;

        foreach ($chainData as $chain) {
            $chainModel = Chain::where('id', $chain['id'])->first();
            $chainModel->name = $chain['name'];
            $chainModel->rpc_endpoint = $chain['rpc_endpoint'];
            $chainModel->save();
        }

        $this->notificationService->success('Chains updated successfully.');

        return redirect()->route('chain.index');
    }
}
