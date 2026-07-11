@extends('blog.layout')

@section('title', 'Blog — Conseils pour réussir tes examens de langue')
@section('description', "Conseils pour préparer le TCF/TEF Canada, l'IELTS, le TOEFL, le DELF-DALF ou le Goethe-Zertifikat depuis le Cameroun et l'Afrique : niveaux visés, durée de préparation, erreurs à éviter.")
@section('canonical', url('/blog'))

@php
    $grouped = collect($posts)->groupBy('category');
@endphp

@section('content')
    <div style="text-align:center; margin-bottom:2.5rem;">
        <span class="badge">Blog PrePla</span>
        <h1 style="margin-top:.6rem;">Conseils pour réussir ton examen de langue</h1>
        <p class="meta" style="font-size:.95rem; max-width:34rem; margin:.6rem auto 0;">
            TCF/TEF Canada, IELTS, TOEFL, DELF-DALF, Goethe-Zertifikat — niveaux à viser,
            durée de préparation réaliste, erreurs à éviter, écrits pour les candidats
            camerounais et africains.
        </p>
    </div>

    @foreach ($grouped as $category => $categoryPosts)
        <h2 style="font-size:1rem; text-transform:uppercase; letter-spacing:.06em; opacity:.6; margin:2.2rem 0 1rem;">{{ $category }}</h2>
        <div class="grid">
            @foreach ($categoryPosts as $post)
                <a href="{{ url('/blog/' . $post['slug']) }}" class="card">
                    @include('blog.partials.banner', ['theme' => $post['theme'] ?? 'method', 'size' => 'thumb'])
                    <h2>{{ $post['title'] }}</h2>
                    <p>{{ $post['description'] }}</p>
                    <p class="meta">{{ \Carbon\Carbon::parse($post['published_at'])->translatedFormat('d M Y') }} · {{ $post['read_minutes'] }} min de lecture</p>
                </a>
            @endforeach
        </div>
    @endforeach
@endsection
