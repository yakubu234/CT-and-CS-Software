<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SmsMessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = TableListing::paginate(
            TableListing::applySearch(
                SmsMessage::query()
                    ->with(['campaign', 'automationRule', 'user.detail', 'branch'])
                    ->latest('id'),
                $request->string('search')->toString(),
                ['phone', 'recipient_name', 'message', 'provider', 'status', 'reference_key']
            ),
            $request,
            15
        );

        return view('bulk-sms.messages.index', [
            'messages' => $messages,
        ]);
    }
}
