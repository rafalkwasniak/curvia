<?php

namespace App\Http\Controllers;

use App\Http\Requests\RssSourceRequest;
use App\Models\RssSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RssSourceController extends Controller
{
    public function index(): View
    {
        return view('rss.index', [
            'sources' => RssSource::orderBy('name')->get(),
        ]);
    }

    public function store(RssSourceRequest $request): RedirectResponse
    {
        RssSource::create($request->validated());

        return redirect()->route('rss.index')->with('status', __('Source added.'));
    }

    public function edit(RssSource $rssSource): View
    {
        return view('rss.edit', ['source' => $rssSource]);
    }

    public function update(RssSourceRequest $request, RssSource $rssSource): RedirectResponse
    {
        $rssSource->update([
            ...$request->validated(),
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('rss.index')->with('status', __('Changes saved.'));
    }

    public function toggle(RssSource $rssSource): RedirectResponse
    {
        $rssSource->update(['active' => ! $rssSource->active]);

        return redirect()->route('rss.index');
    }

    public function destroy(RssSource $rssSource): RedirectResponse
    {
        $rssSource->delete();

        return redirect()->route('rss.index')->with('status', __('Source removed.'));
    }
}
