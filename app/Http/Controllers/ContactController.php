<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input'], 422);
        }

        Mail::raw("Message from {$request->name} ({$request->email}):\n\n{$request->message}", function ($mail) use ($request) {
            $mail->to('oseahumenagboifoh@gmail.com')
                ->from($request->email, $request->name)
                ->subject('New Portfolio Contact');
        });

        return response()->json([
            'message' => 'Email sent successfully',
            'success' => true
        ]);
    }
}
