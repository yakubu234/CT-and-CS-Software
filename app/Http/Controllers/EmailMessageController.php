<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmailMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:email');
    }

    public function index(Request $request): View
    {
        $messages = TableListing::paginate(
            TableListing::applySearch(
                EmailMessage::query()->with(['campaign', 'user.detail', 'branch', 'smtpAccount'])->latest('id'),
                $request->string('search')->toString(),
                ['email', 'recipient_name', 'subject', 'body', 'status']
            ),
            $request,
            20
        );

        return view('email.messages.index', ['messages' => $messages]);
    }
}
