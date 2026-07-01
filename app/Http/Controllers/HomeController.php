<?php

namespace App\Http\Controllers;

use App\Models\AllClass;
use App\Models\City;
use App\Models\Country;
use App\Models\Division;
use App\Models\User;
use App\Models\Notification;
use App\Models\Support;
use App\Models\SupportReplay;
use App\Models\Upazila;
use App\Traits\HttpWebResponse;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    use HttpWebResponse;

    public function front_course()
    {
        return view('course');
    }
    public function superadmin()
    {
        return view('admin.index');
    }

    public function clear_cash()
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        return back()->with('success', 'Cache cleared successfully!');
    }
    public function all_notification()
    {
        $notificationlist = Notification::orderby('created_at', 'desc')->paginate(20);
        return view('admin.pages.notification', [
            'notificationlist' => $notificationlist,
        ]);
    }
    public function read_notification($id)
    {
        $notification = Notification::find($id);
        Notification::where('id', $id)->update([
            'status' => '2',
        ]);
        return back()->with('success', '"' . $notification->name . '" marked as read');
    }
    public function supportlist()
    {
        $userlist = Support::where('user_id', '2')->paginate(15);
        $adminlist = Support::where('user_id', '!=', 1)
            ->where('user_id', '!=', 2)
            ->paginate(15);
        return view('admin.support.saindex', [
            'userlist' => $userlist,
            'adminlist' => $adminlist,
        ]);
    }

    public function support()
    {
        $support = Support::where('user_id', Auth::user()->id)
            ->get()
            ->first();
        $support_user_id = SupportReplay::where('support_id', $support->id)
            ->get()
            ->first();
        $support_replaylist = SupportReplay::where('support_id', $support->id)->get();
        return view('admin.support.aindex', [
            'support_replaylist' => $support_replaylist,
            'support_user_id' => $support_user_id,
        ]);
    }
    public function support_create()
    {
        $support = Support::where('user_id', Auth::user()->id)
            ->get()
            ->first();
        if ($support) {
            return 'You already submited an request, please visit my support. thank you';
        } else {
            return view('admin.support.create', [
                'support_create' => 'off',
            ]);
        }
    }
    public function support_store(Request $request)
    {
        $request->validate([
            'message' => 'required',
        ]);
        $support_id = Support::create([
            'user_id' => Auth::user()->id,
        ])->id;
        SupportReplay::create([
            'support_id' => $support_id,
            'sender_id' => Auth::user()->id,
            'receiver_id' => $request['receiver_id'],
            'message' => $request['message'],
        ]);
        $this->notification(Auth::user()->id, 'An admin request for support please see this as soon as possible.!',  'support', '1');
        return redirect('/support')->with('success', 'You have successfully submited an support request.!');
    }
    public function support_replay_create($id)
    {
        $support_user_id = SupportReplay::where('support_id', $id)
            ->get()
            ->first();
        $support_replaylist = SupportReplay::where('support_id', $id)->get();
        return view('admin.support.aindex', [
            'support_replaylist' => $support_replaylist,
            'support_user_id' => $support_user_id,
        ]);
    }
    public function support_replay_store(Request $request, $id)
    {
        $request->validate([
            'message' => 'required',
        ]);
        SupportReplay::create([
            'support_id' => $id,
            'sender_id' => Auth::user()->id,
            'receiver_id' => $request['receiver_id'],
            'message' => $request['message'],
        ]);
        return back()->with('success', 'You have successfully submited an support request.!');
    }
}
