@extends('blog.layout')

@section('title', $post['title'])
@section('description', $post['description'])
@section('canonical', url('/blog/' . $post['slug']))
@section('og_type', 'article')

@section('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": {!! json_encode($post['title']) !!},
    "description": {!! json_encode($post['description']) !!},
    "datePublished": "{{ $post['published_at'] }}",
    "author": { "@@type": "Organization", "name": "PrePla" },
    "publisher": { "@@type": "Organization", "name": "PrePla" }
}
</script>
@endsection

@section('content')
    <p class="meta"><a href="{{ url('/blog') }}">← Blog</a></p>

    @include('blog.partials.banner', ['theme' => $post['theme'] ?? 'method', 'size' => 'full'])

    <div style="max-width:680px; margin:0 auto;">
        <span class="badge">{{ $post['category'] }}</span>
        <h1>{{ $post['title'] }}</h1>
        <p class="meta">{{ \Carbon\Carbon::parse($post['published_at'])->translatedFormat('d M Y') }} · {{ $post['read_minutes'] }} min de lecture</p>

        <article>
            {!! $post['body'] !!}
        </article>
    </div>

    @if (count($others))
        <div style="max-width:680px; margin:3rem auto 0; border-top:1px solid rgba(19,35,63,0.1); padding-top:1.5rem;">
            <p class="meta" style="text-transform:uppercase; letter-spacing:.04em; font-weight:700;">À lire aussi</p>
            <div class="grid">
                @foreach ($others as $other)
                    <a href="{{ url('/blog/' . $other['slug']) }}" class="card">
                        @include('blog.partials.banner', ['theme' => $other['theme'] ?? 'method', 'size' => 'thumb'])
                        <h2>{{ $other['title'] }}</h2>
                        <p>{{ $other['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
