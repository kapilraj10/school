@php
    $site = [
        'name' => 'YUMAK BAUDDHA MANDAL SCHOOL',
        'tagline' => 'Nursery to Class 10',
        'phone' => '+977 01-5523144',
        'email' => 'info@ybms.com',
        'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
    ];

    $navLinks = ['Home', 'About Us', 'Courses', 'Blog', 'Event', 'Staff', 'Gallery', 'Contact Us'];
    $activeNav = 'Gallery';

    $breadcrumb = ['Home', 'Gallery'];

    $galleryItems = [
        ['img' => asset('images/slide1.png'), 'caption' => 'Classroom Activity'],
        ['img' => asset('images/slide-2.png'), 'caption' => 'Students Learning'],
        ['img' => asset('images/slide-3.png'), 'caption' => 'Campus Event'],
        ['img' => asset('images/logo.png'), 'caption' => 'School Identity'],
        ['img' => asset('images/logo.jpg'), 'caption' => 'School Branding'],
        ['img' => asset('images/inerpageslider.jpg'), 'caption' => 'School Environment'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gallery - {{ $site['name'] }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('css/gallery.css') }}">
  <script defer src="{{ asset('js/gallery.js') }}"></script>
</head>
<body>

<div class="topbar">
  <div class="contact">
    <span>Call us : {{ $site['phone'] }}</span>
    <span>Email : {{ $site['email'] }}</span>
  </div>
  <div class="hours">{{ $site['hours'] }}</div>
</div>

<nav class="navbar">
  <a href="{{ route('home') }}" class="logo">
    <img src="{{ asset('images/logo.png') }}" alt="{{ $site['name'] }} logo" class="logo-image">
    <div class="logo-text">
      <div class="name">{{ $site['name'] }}</div>
      <div class="sub">{{ $site['tagline'] }}</div>
    </div>
  </a>

  <ul class="nav-links">
    @foreach ($navLinks as $link)
      @php
        $url = '#';

        if ($link === 'Home') {
            $url = route('home');
        } elseif ($link === 'About Us') {
            $url = route('about');
        } elseif ($link === 'Gallery') {
            $url = route('gallery');
        }
      @endphp
      <li>
        <a href="{{ $url }}" class="{{ $link === $activeNav ? 'active' : '' }}">
          {{ $link }}
        </a>
      </li>
    @endforeach
  </ul>

  <button class="nav-search" type="button" title="Search">&#128269;</button>
</nav>

<section class="hero">
  <div class="hero-content">
    <h1>Gallery</h1>
    <div class="divider"></div>
    <p class="breadcrumb">
      @foreach ($breadcrumb as $index => $crumb)
        {{ $crumb }}
        @if ($index < count($breadcrumb) - 1)
          <span>/</span>
        @endif
      @endforeach
    </p>
  </div>
</section>

<section class="gallery-section">
  <div class="gallery-grid">
    @foreach ($galleryItems as $index => $item)
      <div class="gallery-item" data-index="{{ $index }}">
        <img
          src="{{ $item['img'] }}"
          alt="{{ $item['caption'] }}"
          loading="lazy"
        />
        <div class="gallery-overlay">
          <div class="overlay-icons">
            <button class="overlay-btn" type="button" title="View">&#128269;</button>
            <button class="overlay-btn" type="button" title="Link">&#128279;</button>
          </div>
          <div class="overlay-caption">{{ $item['caption'] }}</div>
        </div>
      </div>
    @endforeach
  </div>
</section>

<div class="lightbox" id="lightbox">
  <button class="lightbox-close" id="lightbox-close" type="button">&#x2715;</button>
  <button class="lightbox-prev" id="lightbox-prev" type="button">&#8249;</button>
  <img id="lb-img" src="" alt="Gallery image"/>
  <button class="lightbox-next" id="lightbox-next" type="button">&#8250;</button>
</div>

<script>
  window.galleryImages = @json($galleryItems);
</script>

</body>
</html>
