@php
    $site = [
        'name' => 'YUMAK BAUDDHA MANDAL SCHOOL',
        'tagline' => 'Nursery to Class 10',
        'phone' => '+977 01-5523144',
        'email' => 'info@ybms.com',
        'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
    ];

  $navLinks = ['Home', 'About Us', 'Blog', 'Staff', 'Gallery', 'Contact Us'];
    $activeNav = 'Gallery';

    $breadcrumb = ['Home', 'Gallery'];

  $galleryItems = [
    ['img' => asset('images/slide1.png'), 'caption' => 'Classroom Activity', 'category' => 'Classroom'],
    ['img' => asset('images/slide-2.png'), 'caption' => 'Students Learning', 'category' => 'Classroom'],
    ['img' => asset('images/slide-3.png'), 'caption' => 'Campus Event', 'category' => 'Events'],
    ['img' => asset('images/logo.png'), 'caption' => 'School Identity', 'category' => 'Identity'],
    ['img' => asset('images/logo.jpg'), 'caption' => 'School Branding', 'category' => 'Identity'],
    ['img' => asset('images/inerpageslider.jpg'), 'caption' => 'School Environment', 'category' => 'Campus'],
  ];

  $galleryCategories = ['All', 'Classroom', 'Events', 'Campus', 'Identity'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gallery - {{ $site['name'] }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="{{ asset('css/gallery.css') }}">
  <script defer src="{{ asset('js/gallery.js') }}"></script>
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
    <img src="{{ asset('images/logo.png') }}" alt="{{ $site['name'] }} logo" class="logo-image">
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
        } elseif ($link === 'Gallery') {
            $url = route('gallery');
    } elseif ($link === 'Staff') {
      $url = route('staff');
    } elseif ($link === 'Blog') {
      $url = route('blog');
    } elseif ($link === 'Contact Us') {
      $url = route('contact');
        }
      @endphp
      <li>
        <a href="{{ $url }}" class="{{ $link === $activeNav ? 'active' : '' }}">
          {{ $link }}
        </a>
      </li>
    @endforeach
  </ul>

  <button class="nav-search" type="button" title="Search"><i class="fa fa-search"></i></button>
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
  <div class="gallery-filters" id="galleryFilters">
    @foreach ($galleryCategories as $category)
      <button
        class="filter-btn {{ $category === 'All' ? 'active' : '' }}"
        type="button"
        data-category="{{ strtolower($category) }}"
      >
        {{ $category }}
      </button>
    @endforeach
  </div>

  <div class="gallery-grid">
    @foreach ($galleryItems as $index => $item)
      <div class="gallery-item" data-index="{{ $index }}" data-category="{{ strtolower($item['category']) }}">
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

<footer>
  <div class="footer-grid">
    <div class="footer-col">
      <h5>About School</h5>
      <p>Yumak Bauddha Mandal School provides quality education from Nursery to Class 10 with focus on values, creativity, and academic excellence.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <h5>Quick Links</h5>
      <ul>
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('about') }}">About Us</a></li>
        <li><a href="{{ route('blog') }}">Blog</a></li>
        <li><a href="{{ route('staff') }}">Staff</a></li>
        <li><a href="{{ route('gallery') }}">Gallery</a></li>
        <li><a href="{{ route('contact') }}">Contact Us</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>School Highlights</h5>
      <ul>
        <li><a href="#">Play Ground</a></li>
        <li><a href="#">Music and Dance</a></li>
        <li><a href="#">Arts and Crafts</a></li>
        <li><a href="#">Safe Transportation</a></li>
        <li><a href="#">Healthy Food</a></li>
        <li><a href="#">Educational Tour</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Contact Info</h5>
      <ul>
        <li><a href="#"><i class="fa fa-map-marker-alt" style="color:var(--gold);margin-right:8px"></i> Kathmandu, Nepal</a></li>
        <li><a href="tel:+977015523144"><i class="fa fa-phone" style="color:var(--gold);margin-right:8px"></i> +977 01-5523144</a></li>
        <li><a href="mailto:info@ybms.com"><i class="fa fa-envelope" style="color:var(--gold);margin-right:8px"></i> info@ybms.com</a></li>
        <li><a href="#"><i class="fa fa-clock" style="color:var(--gold);margin-right:8px"></i> Sun–Fri: 9:00am–5:30pm</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; 2026 Yumak Bauddha Mandal School. All Rights Reserved.
  </div>
</footer>

</body>
</html>
