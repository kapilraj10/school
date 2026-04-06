@php
    $pageTitle = 'About Us - Yumak Bauddha Mandal School';
  $site = [
    'name' => 'YUMAK BAUDDHA MANDAL SCHOOL',
    'tagline' => 'Nursery to Class 10',
    'phone' => '+977 01-5523144',
    'email' => 'info@ybms.com',
    'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
  ];
  $navLinks = ['Home', 'About Us', 'Courses', 'Blog', 'Event', 'Staff', 'Gallery', 'Contact Us'];
  $activeNav = 'About Us';
    $breadcrumb = ['Home', 'About Us'];

    $about = [
        'heading' => 'WELCOME TO',
        'school' => 'YUMAK BAUDDHA MANDAL SCHOOL',
        'description' => 'We are committed to providing quality school education that empowers students to reach their full potential. Our school combines academic excellence with practical learning from Nursery to Class 10.',
        'highlights' => [
            'Our Mission and Philosophy',
            'Our Classes and Programmes',
            'Why Parents Trust Us',
            'Strong Learning Outcomes',
        ],
    ];

    $knowledge = [
        'title' => 'GET THE BEST KNOWLEDGE FROM US',
        'subtitle' => 'HAVE THE COURAGE TO HAVE CONVICTIONS',
        'desc' => 'We believe every child deserves access to quality education. Our experienced teachers and student-friendly environment ensure learning thrives in every class.',
        'cta' => 'JOIN US',
    ];

    $courses = [
        'title' => 'OUR COURSES PROGRESS',
        'description' => 'Our balanced curriculum helps students develop language, technology, social and life skills needed for the future.',
        'cta' => 'LEARN MORE',
        'progress' => [
            ['label' => 'ENGLISH & LANGUAGE', 'pct' => 45],
            ['label' => 'SOCIAL STUDIES', 'pct' => 55],
            ['label' => 'SCIENCE & COMPUTER', 'pct' => 75],
            ['label' => 'OVERALL STUDENT DEVELOPMENT', 'pct' => 100],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $pageTitle }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('css/about.css') }}">
  <script src="{{ asset('js/about.js') }}" defer></script>
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
        <a href="{{ $url }}" class="{{ $link === $activeNav ? 'active' : '' }}">{{ $link }}</a>
      </li>
    @endforeach
  </ul>

  <button class="nav-search" type="button" title="Search">&#128269;</button>
</nav>

<section class="hero-banner">
  <div class="hero-content">
    <h1>{{ strtoupper(end($breadcrumb)) }}</h1>
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

<section class="section">
  <div class="container">
    <div class="about-grid">
      <div class="about-text">
        <h2>
          {{ $about['heading'] }}
          <span>{{ $about['school'] }}</span>
        </h2>
        <div class="section-line"></div>
        <p>{{ $about['description'] }}</p>
        <ul class="about-list">
          @foreach ($about['highlights'] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
      <div class="about-img">
        <img src="/images/slide1.png" alt="Students"/>
      </div>
    </div>
  </div>
</section>

<section class="knowledge-section">
  <div class="knowledge-grid">
    <div class="knowledge-img">
      <img src="/images/slide-2.png" alt="School campus"/>
    </div>
    <div class="knowledge-content">
      <h2>
        GET THE <em>BEST KNOWLEDGE</em><br/>FROM US
      </h2>
      <div class="section-line"></div>
      <h3>{{ $knowledge['subtitle'] }}</h3>
      <p>{{ $knowledge['desc'] }}</p>
      <a href="#" class="btn-gold">{{ $knowledge['cta'] }}</a>
    </div>
  </div>
</section>

<section class="progress-section">
  <div class="container">
    <div class="progress-grid">
      <div class="progress-left">
        <h2>OUR COURSES <span>PROGRESS</span></h2>
        <div class="section-line"></div>
        <p>{{ $courses['description'] }}</p>
        <a href="#" class="btn-gold">{{ $courses['cta'] }}</a>
      </div>
      <div class="progress-bars">
        @foreach ($courses['progress'] as $bar)
          <div class="bar-item">
            <div class="bar-label">
              <span>{{ $bar['label'] }}</span>
              <div class="bar-pct">{{ $bar['pct'] }}%</div>
            </div>
            <div class="bar-track">
              <div class="bar-fill" data-pct="{{ $bar['pct'] }}"></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

</body>
</html>
