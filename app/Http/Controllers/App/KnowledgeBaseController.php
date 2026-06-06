<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeBase\StoreKbArticleRequest;
use App\Models\KbArticle;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = $this->canManage();
        $query = KbArticle::query()
            ->when(! $canManage, fn ($builder) => $builder->where('status', 'published'))
            ->when($request->string('q')->isNotEmpty(), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $builder->where(function ($nested) use ($term): void {
                    $nested
                        ->where('title', 'like', $term)
                        ->orWhere('body_markdown', 'like', $term);
                });
            })
            ->orderByRaw("case when status = 'published' then 0 else 1 end")
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at');

        return view('app.kb.index', [
            'articles' => $query->paginate(12)->withQueryString(),
            'filters' => ['q' => $request->string('q')->toString()],
            'canManage' => $canManage,
        ]);
    }

    public function create(): View
    {
        abort_unless($this->canManage(), 403);

        return view('app.kb.create');
    }

    public function store(StoreKbArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $membership = app(TenantContext::class)->membership();

        $article = KbArticle::query()->create([
            'author_membership_id' => $membership?->id,
            'title' => $data['title'],
            'body_markdown' => $data['body_markdown'],
            'body_html_cached' => $this->renderMarkdown($data['body_markdown']),
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return redirect()->route('app.kb.show', $article->slug)->with('status', 'kb-created');
    }

    public function edit(string $article): View
    {
        abort_unless($this->canManage(), 403);

        return view('app.kb.edit', [
            'article' => $this->findArticleForManagement($article),
        ]);
    }

    public function update(StoreKbArticleRequest $request, string $article): RedirectResponse
    {
        $articleModel = $this->findArticleForManagement($article);
        $data = $request->validated();
        $wasPublished = $articleModel->status === 'published';
        $slug = $articleModel->title === $data['title']
            ? $articleModel->slug
            : KbArticle::uniqueSlugFor($articleModel->company_id, $data['title'], $articleModel->id);

        $articleModel->forceFill([
            'title' => $data['title'],
            'slug' => $slug,
            'body_markdown' => $data['body_markdown'],
            'body_html_cached' => $this->renderMarkdown($data['body_markdown']),
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($wasPublished ? $articleModel->published_at : now())
                : null,
        ])->save();

        return redirect()->route('app.kb.show', $articleModel->slug)->with('status', 'kb-updated');
    }

    public function archive(string $article): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $articleModel = $this->findArticleForManagement($article);
        $articleModel->forceFill([
            'status' => 'archived',
            'published_at' => null,
        ])->save();

        return redirect()->route('app.kb.show', $articleModel->slug)->with('status', 'kb-archived');
    }

    public function destroy(string $article): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        $this->findArticleForManagement($article)->delete();

        return redirect()->route('app.kb.index')->with('status', 'kb-deleted');
    }

    public function show(string $article): View
    {
        $articleModel = KbArticle::query()
            ->where('slug', $article)
            ->when(! $this->canManage(), fn ($builder) => $builder->where('status', 'published'))
            ->firstOrFail();

        return view('app.kb.show', [
            'article' => $articleModel,
            'canManage' => $this->canManage(),
        ]);
    }

    private function canManage(): bool
    {
        return in_array(app(TenantContext::class)->membership()?->role, ['admin', 'supervisor'], true);
    }

    private function findArticleForManagement(string $article): KbArticle
    {
        return KbArticle::query()
            ->where('slug', $article)
            ->firstOrFail();
    }

    private function renderMarkdown(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
