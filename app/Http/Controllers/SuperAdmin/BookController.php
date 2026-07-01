<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\BookItem;
use App\Models\Notice;
use App\Services\FcmService;
use App\Traits\HttpWebResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use HttpWebResponse;
    public function book_index()
    {
        $chapter_books = Book::latest()->get();
        $books = Book::latest()->paginate(10);

        return view('admin.book.index', compact('books', 'chapter_books'));
    }
    public function book_store(Request $request)
    {
        $books = Book::createStore($request);
        if ($books) {
            return back()->with('success', $request['title'] . ' - added as a new book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }
    public function book_edit($slug)
    {
        $books = Book::latest()->paginate(10);
        $bookf = Book::where('slug', $slug)->first();
        return view('admin.book.bedit', compact('books', 'bookf'));
    }
    public function book_update(Request $request, $id)
    {
        $books = Book::updateStore($request, $id);
        if ($books) {
            return back()->with('success', $request['title'] . ' - update book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }

    public function chapter_index($slug)
    {
        $book = Book::where('slug', $slug)->first();
        $bookChapters = BookChapter::latest()->where('book_id', $book->id)->paginate(10);
        return view('admin.book.chapter', compact('bookChapters', 'book'));
    }

    public function chapter_store(Request $request)
    {
        $chapter = BookChapter::createStore($request);
        if ($chapter) {
            return back()->with('success', $request['title'] . ' - added as a new chapter for book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }
    public function chapter_edit($slug)
    {
        $bookChapterf = BookChapter::where('slug', $slug)->first();
        $bookChapters = BookChapter::latest()->where('book_id', $bookChapterf->book_id)->paginate(10);
        $book = Book::find($bookChapterf->book_id);
        return view('admin.book.cedit', compact('bookChapters', 'bookChapterf', 'book'));
    }
    public function chapter_update(Request $request, $id)
    {
        $chapter = BookChapter::updateStore($request, $id);
        if ($chapter) {
            $uChapter = BookChapter::find($id);
            return redirect(route('chapter.edit', ['slug' => $uChapter->slug]))->with('success', $request['title'] . ' - update chapter for book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }
    public function chapter_delete($id)
    {
        $bookItems = BookItem::where('chapter_id', $id)->get();
        foreach ($bookItems as $bookItem) {
            $bookItem->delete();
        }
        BookChapter::find($id)->delete();
        return redirect(route('book.index'))->with('success', 'Post deleted successfull...!');
    }

    public function item_index($slug)
    {
        $bookChapter = BookChapter::where('slug', $slug)->first();
        $id = $bookChapter->id;
        $bookItems = BookItem::latest()->where('chapter_id', $id)->paginate(10);
        return view('admin.book.item', compact('bookItems', 'id'));
    }

    public function item_store(Request $request)
    {
        $books = BookItem::createStore($request);
        if ($books) {
            return back()->with('success', $request['title'] . ' - added as a new post for book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }
    public function item_edit($slug)
    {
        $bookItemf = BookItem::where('slug', $slug)->first();
        $bookItems = BookItem::latest()->where('chapter_id', $bookItemf->chapter_id)->paginate(10);
        return view('admin.book.iedit', compact('bookItems', 'bookItemf'));
    }
    public function item_show($slug)
    {
        $bookItemf = BookItem::where('slug', $slug)->first();
        $bookItems = BookItem::latest()->where('chapter_id', $bookItemf->chapter_id)->paginate(10);
        return view('admin.book.ishow', compact('bookItems', 'bookItemf'));
    }

    public function item_notification($slug)
    {
        $bookItemf = BookItem::where('slug', $slug)->first();
        $bookItems = BookItem::latest()->where('chapter_id', $bookItemf->chapter_id)->paginate(10);
        return view('admin.book.notification', compact('bookItems', 'bookItemf'));
    }

    public function item_notification_store(Request $request, FcmService $fcm)
    {
        $notice = new Notice();
        $notice->title = $request->title;
        $notice->app = $request->app;
        $notice->body = $request->body;
        $notice->slug = $request->slug;
        $notice->save();
        $fcm->sendToTopic('abmnmenglish', $notice->title, $request->body, [
            'type' => 'item',
            'slug' => $request->slug
        ]);
        return back()->with('success', $request['title'] . ' send Notification...!');
    }

    public function item_update(Request $request, $id)
    {
        $bookItems = BookItem::updateStore($request, $id);
        if ($bookItems) {
            $bookItem = BookItem::find($id);
            return back()->with('success', $request['title'] . ' - update post for book...!');
        } else {
            return back()->with('warning', 'Error check all data again and submit again...!');
        }
    }
    public function item_delete($id)
    {
        BookItem::find($id)->delete();
        return redirect(route('book.index'))->with('success', 'Post deleted successfull...!');
    }
}
