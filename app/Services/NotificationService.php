<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class NotificationService
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function success($message)
    {
        $this->pushNotification('success', $message);
    }

    public function info($message)
    {
        $this->pushNotification('info', $message);
    }

    public function error($message)
    {
        $this->pushNotification('error', $message);
    }

    public function handleValidationErrors($validator)
    {
        $messages = $validator->getMessageBag()->all();

        foreach ($messages as $message) {
            $this->error($message);
        }
    }

    private function pushNotification($type, $message)
    {
        $notifications = $this->request->session()->get('notifications', []);
        $notifications[] = ['type' => $type, 'message' => $message];
        $this->request->session()->flash('notifications', $notifications);
    }
}
