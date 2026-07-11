@extends('blog.layout')

@section('title', 'Blog — Conseils pour réussir tes examens de langue')
@section('description', "Conseils pour préparer le TCF/TEF Canada, l'IELTS, le DELF-DALF ou le Goethe-Zertifikat depuis le Cameroun et l'Afrique.")
@section('canonical', url('/blog'))

@section('content')
    <h1>Blog PrePla</h1>
    <p class="meta" style="margin-bottom:2rem;">Conseils concrets pour préparer tes examens de langue — TCF/TEF Canada, IELTS, DELF-DALF, Goethe-Zertifikat.</p>

    @foreach ($posts as $post)
        <a href="{{ url('/blog/' . $post['slug']) }}" class="card">
            <span class="badge">{{ $post['category'] }}</span>
            <h2>{{ $post['title'] }}</h2>
            <p>{{ $post['description'] }}</p>
            <p class="meta">{{ \Carbon\Carbon::parse($post['published_at'])->translatedFormat('d M Y') }} · {{ $post['read_minutes'] }} min de lecture</p>
        </a>
    @endforeach
@endsection
