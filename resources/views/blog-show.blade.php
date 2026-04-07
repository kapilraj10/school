@php
  $site = [
    'phone' => '+977 01-5523144',
    'email' => 'info@ybms.com',
    'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
  ];

  $navLinks = ['Home', 'About Us', 'Blog', 'Our Team', 'Gallery', 'Contact Us'];
  $activeNav = 'Blog';

  $imageUrl = $post->featured_image;

  if (blank($imageUrl)) {
    $imageUrl = asset('images/slide1.png');
  } elseif (!\Illuminate\Support\Str::startsWith($imageUrl, ['http://', 'https://'])) {
    $imageUrl = asset('storage/'.$imageUrl);
  }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $post->title }} - Yumak Bauddha Mandal School</title>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="{{ asset('css/home.css') }}">
  <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
</head>
<body>

<div class="topbar">
  <div class="contact">
    <span><i class="fa fa-phone"></i> Call us : {{ $site['phone'] }}</span>
    <span><i class="fa fa-envelope"></i> Email : {{ $site['email'] }}</span>
  </div>
  <div class="hours">
    <i class="fa fa-clock"></i> {{ $site['hours'] }}
  </div>
</div>

<nav>
  <a href="{{ route('home') }}" class="logo">
    <img src="{{ asset('images/logo.png') }}" alt="Yumak Bauddha Mandal School logo" class="logo-image">
    <div class="logo-text">
      <strong>Y.B.M</strong>
      <span>Enlightening Minds, Shaping Futures</span>
    </div>
  </a>
  <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="primaryNav">
    <i class="fa fa-bars" aria-hidden="true"></i>
  </button>
  <ul class="nav-links" id="primaryNav">
    @foreach ($navLinks as $link)
      @php
        $url = '#';

        if ($link === 'Home') {
            $url = route('home');
        } elseif ($link === 'About Us') {
            $url = route('about');
        } elseif ($link === 'Blog') {
            $url = route('blog');
        } elseif ($link === 'Our Team') {
            $url = route('staff');
        } elseif ($link === 'Gallery') {
            $url = route('gallery');
        } elseif ($link === 'Contact Us') {
            $url = route('contact');
        }
      @endphp
      <li><a href="{{ $url }}" class="{{ $link === $activeNav ? 'active' : '' }}">{{ $link }}</a></li>
    @endforeach
  </ul>
  <button class="nav-search" type="button"><i class="fa fa-search"></i></button>
</nav>

<div class="page-header" style="background-image: linear-gradient(rgb(0 0 0 / 72%), rgb(0 0 0 / 72%)), url('{{ asset('images/inerpageslider.jpg') }}');">
  <h1>{{ strtoupper($post->title) }}</h1>
  <p class="page-subtitle">School Blog</p>
  <p class="breadcrumb">
    <a href="{{ route('home') }}">Home</a> /
    <a href="{{ route('blog') }}">Blog</a> /
    <span>{{ \Illuminate\Support\Str::limit($post->title, 30) }}</span>
  </p>
</div>

<div class="container blog-container">
  <div class="blog-layout">
    <main>
      <article class="post-item">
        <div class="post-thumb">
          <img src="{{ $imageUrl }}" alt="{{ $post->title }}"/>
        </div>

        <h2 class="post-title" style="margin-top: 1rem;">{{ strtoupper($post->title) }}</h2>

        <div class="post-meta">
          <span><i class="fa fa-user"></i> {{ $post->author?->name ?? 'Admin' }}</span>
          <span><i class="fa fa-clock"></i> {{ $post->published_at?->format('M d, Y') ?? $post->created_at?->format('M d, Y') }}</span>
        </div>

        @if (filled($post->excerpt))
          <p class="post-excerpt">{{ $post->excerpt }}</p>
        @endif

        <div class="post-excerpt" style="white-space: normal;">
          {!! $post->content !!}
        </div>

        @if (is_array($post->tags) && $post->tags !== [])
          <div class="tags-cloud" style="margin-top: 1rem;">
            @foreach ($post->tags as $tag)
              <span class="tag">{{ $tag }}</span>
            @endforeach
          </div>
        @endif

        <a href="{{ route('blog') }}" class="btn-gold" style="margin-top: 1rem; display: inline-block;">BACK TO BLOG</a>
      </article>
    </main>

    <aside class="sidebar">
      <div class="widget">
        <div class="widget-title"><em>RECENT</em> POSTS</div>
        @foreach ($recentPosts as $item)
          <div class="popular-item">
            <div class="popular-info">
              <h5><a href="{{ route('blog.show', $item->slug) }}">{{ $item->title }}</a></h5>
              <span class="date">{{ $item->published_at?->format('M d, Y') ?? $item->created_at?->format('M d, Y') }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </aside>
  </div>
</div>

<script src="{{ asset('js/blog.js') }}"></script>
</body>
</html>
