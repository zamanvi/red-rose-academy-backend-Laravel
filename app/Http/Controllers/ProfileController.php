<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Traits\HttpWebResponse;
use App\Models\Friend;
use App\Models\User;
use App\Models\Notification;
use App\Models\ReceiveRequest;
use App\Models\SendRequest;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use HttpWebResponse;

    public function friends()
    {
        $friendsList = Friend::where('user_id', Auth::user()->id)
            ->where('friend_id', '!=', 1)
            ->get();
        return view('frontend.profile.mfriend', [
            'friendsList' => $friendsList,
        ]);
    }
    public function find_friend()
    {
        $userList = User::where('user_type', 2)
            ->where('id', '!=', Auth::user()->id)
            ->get();
        $friendList = Friend::where('user_id', Auth::user()->id)->get();
        return view('frontend.profile.ffriend', [
            'userList' => $userList,
            'friendList' => $friendList,
        ]);
    }
    public function allrequest()
    {
        $receiveRequestList = ReceiveRequest::where('user_id', Auth::user()->id)->get();
        return view('frontend.profile.request', [
            'receiveRequestList' => $receiveRequestList,
        ]);
    }
    public function allsend()
    {
        $sendRequestList = SendRequest::where('user_id', Auth::user()->id)->get();
        return view('frontend.profile.send', [
            'sendRequestList' => $sendRequestList,
        ]);
    }
    public function add_friend($id)
    {
        $sendrequest = SendRequest::where('user_id', Auth::user()->id)
            ->where('request_id', $id)
            ->first();
        $receiverequest = ReceiveRequest::where('request_id', Auth::user()->id)
            ->where('user_id', $id)
            ->first();
        if ($sendrequest && $receiverequest) {
            return $this->apiResponse(['sendrequest' => $sendrequest], 'Already friend request sended.!', 202);
        } else {
            if ($id != Auth::user()->id) {
                SendRequest::create([
                    'user_id' => Auth::user()->id,
                    'request_id' => $id,
                ]);
                ReceiveRequest::create([
                    'user_id' => $id,
                    'request_id' => Auth::user()->id,
                ]);
                $user = User::find($id);
                $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" send friend request, to "' . $user->name . '" send successful.!',  'request', '1');
                return redirect('/profile')->with('success', 'Friend request send successfull');
            }
        }
    }
    public function unfriend($id)
    {
        $friend = Friend::where('friend_id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" unfriend, "' . $friend->user->name . '" successful.!',  'unfriend', '1');
        $friend->delete();
        return redirect('/profile')->with('success', 'Unfriend successfull');
    }
    public function cancelrequest($id)
    {
        $sendrequest = SendRequest::where('user_id', Auth::user()->id)
            ->where('request_id', $id)
            ->get()
            ->first();
        $receiverequest = ReceiveRequest::where('request_id', Auth::user()->id)
            ->where('user_id', $id)
            ->get()
            ->first();
        $user = User::find($id);
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" cancel friend request, "' . $user->name . '" successful.!',  'cancelrequest', '1');
        $sendrequest->delete();
        $receiverequest->delete();
        return redirect('/profile')->with('success', 'Friend request cancel successfull');
    }
    public function confirmrequest($id)
    {
        $sendrequest = SendRequest::where('user_id', $id)
            ->where('request_id', Auth::user()->id)
            ->get()
            ->first();
        $receiverequest = ReceiveRequest::where('request_id', $id)
            ->where('user_id', Auth::user()->id)
            ->get()
            ->first();
        $user = User::find($receiverequest->request_id);
        $this->confirm_request($receiverequest->request_id, Auth::user()->id, '1');
        $this->notification(Auth::user()->id, '"' . Auth::user()->name . '" Confirm friend request, "' . $user->name . '" successful.!',  'confirmrequest', '1');
        $sendrequest->delete();
        $receiverequest->delete();
        return redirect('/profile')->with('success', 'Friend request confirm successfull');
    }

    public function makemessage(Request $request, $id)
    {
        $my_id = Auth::user()->id;
        $chat = Chat::where('receiver_id', $id)
            ->where('sender_id', $my_id)
            ->get()
            ->first();
        if ($chat) {
            Chat::where('id', $chat->id)->update([
                'lastmessage' => $request->input('message'),
            ]);
            $this->message($chat->id, $request->input('message'), $my_id, $id, false, 'text');
            return back()->with('success', 'Message send successful');
        } else {
            $chat1 = Chat::where('receiver_id', $my_id)
                ->where('sender_id', $id)
                ->get()
                ->first();
            if ($chat1) {
                Chat::where('id', $chat1->id)->update([
                    'lastmessage' => $request->input('message'),
                ]);
                $this->message($chat1->id, $request->input('message'), $my_id, $id, false, 'text');
                return back()->with('success', 'Message send successful');
            } else {
                $chat_id = Chat::create([
                    'user_id' => $my_id,
                    'sender_id' => $my_id,
                    'receiver_id' => $id,
                    'lastmessage' => $request->input('message'),
                ])->id;
                $this->message($chat_id, $request->input('message'), $my_id, $id, false, 'text');
                return back()->with('success', 'Message send successful');
            }
        }
    }
    public function chat()
    {
        $chatList = Chat::orderby('updated_at', 'desc')
            ->where('user_id', Auth::user()->id)
            ->where('sender_id', Auth::user()->id)
            ->orwhere('receiver_id', Auth::user()->id)
            ->get();

        return view('frontend.profile.message', [
            'chatList' => $chatList,
            'messagelist' => '',
            'user' => 'off',
        ]);
    }
    public function chatmessage($id)
    {
        $my_id = Auth::user()->id;
        $chatList = Chat::orderby('updated_at', 'desc')
            ->where('user_id', $my_id)
            ->where('sender_id', $my_id)
            ->orwhere('receiver_id', $my_id)
            ->get();
        $messagelist = Message::where('chat_id', $id)->get();
        $chat = Chat::find($id);
        $user = '';
        if ($my_id == $chat->receiver_id) {
            $user = User::find($chat->sender_id);
        } elseif ($my_id == $chat->sender_id) {
            $user = User::find($chat->receiver_id);
        }

        $friendsList = Friend::where('user_id', $my_id)
            ->where('friend_id', '!=', 1)
            ->get();

        return view('frontend.profile.message', [
            'chatList' => $chatList,
            'chatid' => $id,
            'friendsList' => $friendsList,
            'messagelist' => $messagelist,
            'user' => $user,
        ]);
    }
    public function chatmessage_store(Request $request, $id)
    {
        Chat::where('id', $id)->update([
            'lastmessage' => $request->input('message'),
        ]);
        $this->message($id, $request->input('message'), Auth::user()->id, $request->input('receiver_id'), false, 'text');
        return back();
    }
    public function sendpoints(Request $request, $id)
    {
        $my_id = Auth::user()->id;
        $receiver = User::find($id);
        $sender = User::find($my_id);

        if ($sender->profile->points >= (int) $request->input('message')) {
            $totalplus = $receiver->profile->points + $request->input('message');
            $totalminus = $sender->profile->points - $request->input('message');

            Chat::where('id', $request->input('chatid'))->update([
                'lastmessage' => 'Point Sharing',
            ]);

            $this->pointupdate($id, $totalplus);
            $this->pointupdate($my_id, $totalminus);
            $this->history($id, 'You received ' . $request->input('message') . ' points from ' . $sender->name, 'points');
            $this->history($my_id, 'You send ' . $request->input('message') . ' points to ' . $receiver->name, 'points');

            $this->message($request->input('chatid'), $request->input('message'), $my_id, $id, false, 'points');
            $this->notification(Auth::user()->id, '"' . $sender->name . '" send ' . $request->input('message') . ' points to "' . $receiver->name . '" successfully.!',  'points', '1');
            return back();
        } else {
            return back()->with('warning', 'Insufficient points for sharing.!');
        }
    }
    public function sendcard(Request $request, $id)
    {
        $my_id = Auth::user()->id;
        $receiver = User::find($id);
        $sender = User::find($my_id);
        $message = $request->input('message');
        $cardname = User::find($message);

        Chat::where('id', $request->input('chatid'))->update([
            'lastmessage' => 'Contact card Sharing',
        ]);

        $this->history($id, 'You received an contact card ' . $message . ' from ' . $sender->name, 'contactcard');
        $this->history($my_id, 'You send an contact card ' . $message . ' to ' . $receiver->name, 'contactcard');

        $this->message($request->input('chatid'), $message, $my_id, $id, false, 'contactcard');
        $this->notification(Auth::user()->id, '"' . $sender->name . '" send contact card of ' . $cardname->name . ' to "' . $receiver->name . '" successfully.!',  'contactcard', '1');
        return back();
    }
}
