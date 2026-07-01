<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Notice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class NoticeController extends Controller
{
    /**
     * API: return notices for grammar app
     */
    public function apiIndex()
    {
        $notices = Notice::where('app', 'grammar')
            ->latest()
            ->paginate(20);
        return ApiResponse::respond(['notices' => $notices], true, 'All notices', Response::HTTP_OK);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notices = Notice::latest()->paginate(20);
        return view('admin.notices.index', compact('notices'));
    }

    public function create()
    {
        $notices = Notice::paginate(20);
        return view('admin.notices.index', compact('notices'));
    }

    public function store(Request $request, FcmService $fcm)
    {
        $notice = new Notice();
        $notice->title = $request->title;
        $notice->app = $request->app;
        $notice->body = $request->body;
        $notice->save();
        $fcm->sendToTopic('abmn', Str::limit($notice->title, 50), $request->content, [
            'type' => 'notice',
            'id' => (string) $notice->id,
        ]);
        return redirect()->route('notices.index')->with('success', 'Notice created successfully.');
    }

    public function show($id)
    {
        $notice = Notice::findOrFail($id);
        $notices = Notice::paginate(20);
        return view('admin.notices.show', compact('notice', 'notices'));
    }

    public function edit($id)
    {
        $notice = Notice::findOrFail($id);
        $notices = Notice::paginate(20);
        return view('admin.notices.edit', compact('notice', 'notices'));
    }

    public function update(Request $request, $id, FcmService $fcm)
    {
        $notice = Notice::findOrFail($id);
        $notice->title = $request->title;
        $notice->app = $request->app;
        $notice->body = $request->body;
        $notice->save();
        $fcm->sendToTopic('abmnmenglish', Str::limit($notice->title, 50), 'Add Notice, Check this out', [
            'type' => 'notice',
            'id' => (string) $notice->id,
        ]);
        return redirect()->route('notices.index')->with('success', 'Notice update successfully.');
    }

    public function destroy(string $id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();
        return redirect()->route('notices.index')->with('success', 'Notice deleted successfully.');
    }
}