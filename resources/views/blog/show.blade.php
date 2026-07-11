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
    <span class="badge">{{ $post['category'] }}</span>
    <h1>{{ $post['title'] }}</h1>
    <p class="meta">{{ \Carbon\Carbon::parse($post['published_at'])->translatedFormat('d M Y') }} · {{ $post['read_minutes'] }} min de lecture</p>

    <article>
        {!! $post['body'] !!}
    </article>

    @if (count($others))
        <div style="margin-top:3rem; border-top:1px solid rgba(19,35,63,0.1); padding-top:1.5rem;">
            <p class="meta" style="text-transform:uppercase; letter-spacing:.04em; font-weight:700;">À lire aussi</p>
            @foreach ($others as $other)
                <a href="{{ url('/blog/' . $other['slug']) }}" class="card">
                    <span class="badge">{{ $other['category'] }}</span>
                    <h2>{{ $other['title'] }}</h2>
                    <p>{{ $other['description'] }}</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
