<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\ContactMessage;
use App\Models\Distributor;
use App\Models\Game;
use App\Models\Page;
use App\Models\Post;
use App\Models\Winner;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    /** Pages that are just copy, served from /{key}. */
    public const COPY_PAGES = ['about', 'how-to-play', 'rules', 'privacy', 'become-a-distributor'];

    public function home(): View
    {
        return view('site.home', [
            'page' => Page::for('home'),
            'games' => Game::query()->upcoming()->limit(4)->get(),
            'distributors' => Distributor::query()->active()->get(),
            'winners' => Winner::query()->published()->limit(3)->get(),
        ]);
    }

    public function page(string $key): View
    {
        return view('site.page', ['page' => Page::query()->where('key', $key)->firstOrFail()]);
    }

    public function schedule(): View
    {
        return view('site.schedule', [
            'page' => Page::for('schedule'),
            'games' => Game::query()->upcoming()->limit(26)->get(),
        ]);
    }

    public function watch(): View
    {
        return view('site.watch', ['page' => Page::for('watch-live')]);
    }

    public function distributors(): View
    {
        return view('site.distributors', [
            'page' => Page::for('distributors'),
            'distributors' => Distributor::query()->active()->get(),
        ]);
    }

    public function winners(): View
    {
        return view('site.winners', [
            'page' => Page::for('winners'),
            'winners' => Winner::query()->published()->paginate(12),
        ]);
    }

    public function blog(): View
    {
        return view('blog.index', [
            'page' => Page::for('blog'),
            'posts' => Post::query()->live()->paginate(9),
        ]);
    }

    /** Signed-in admins can open drafts and scheduled posts to preview them. */
    public function post(Request $request, Post $post): View
    {
        $isLive = $post->status === PostStatus::Published && $post->published_at?->isPast();

        abort_unless($isLive || $request->user(), 404);

        return view('blog.show', ['post' => $post]);
    }

    public function contact(): View
    {
        return view('site.contact', ['page' => Page::for('contact')]);
    }

    public function sendContact(Request $request, Settings $settings): RedirectResponse
    {
        // Bots fill in the hidden field; pretend it worked.
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('sent', true);
        }

        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ContactMessage::create($data);

        if ($to = $settings->get('business_email')) {
            try {
                Mail::raw(
                    "{$message->topicLabel()} from {$message->name} <{$message->email}>"
                        .($message->phone ? "\nPhone: {$message->phone}" : '')
                        ."\n\n{$message->message}",
                    fn ($mail) => $mail->to($to)->replyTo($message->email, $message->name)
                        ->subject('Website message: '.$message->topicLabel()),
                );
            } catch (\Throwable $e) {
                // Saved above and visible in the admin; don't lose the visitor's message over mail.
                report($e);
            }
        }

        return redirect()->route('contact')->with('sent', true);
    }
}
