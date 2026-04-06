@php
    $site = [
        'name' => 'YUMAK BAUDDHA MANDAL SCHOOL',
        'tagline' => 'Nursery to Class 10',
        'phone' => '+977 01-5523144',
        'email' => 'info@ybms.com',
        'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
    ];

  $navLinks = ['Home', 'About Us', 'Blog', 'Staff', 'Gallery', 'Contact Us'];
    $activeNav = 'Staff';
    $breadcrumb = ['Home', 'Our Teachers'];

    $teachers = [
        ['name' => 'Aarati Sharma', 'role' => 'English Teacher', 'img' => asset('images/slide1.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Bikash Thapa', 'role' => 'Science Teacher', 'img' => asset('images/slide-2.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Nima Lama', 'role' => 'Math Teacher', 'img' => asset('images/slide-3.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Sujata Karki', 'role' => 'Computer Teacher', 'img' => asset('images/logo.jpg'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Dipesh Rai', 'role' => 'Social Teacher', 'img' => asset('images/inerpageslider.jpg'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Mina Gurung', 'role' => 'Nepali Teacher', 'img' => asset('images/slide1.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Rohan Shrestha', 'role' => 'Art Teacher', 'img' => asset('images/slide-2.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
        ['name' => 'Pooja Adhikari', 'role' => 'Music Teacher', 'img' => asset('images/slide-3.png'), 'twitter' => '#', 'facebook' => '#', 'linkedin' => '#'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Our Teachers - {{ $site['name'] }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Oswald:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="{{ asset('css/staff.css') }}">
  <script defer src="{{ asset('js/staff.js') }}"></script>
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
        <a href="{{ $url }}" class="{{ $link === $activeNav ? 'active' : '' }}">{{ $link }}</a>
      </li>
    @endforeach
  </ul>
  <button class="nav-search" type="button"><i class="fa fa-search"></i></button>
</nav>

<section class="hero">
  <div class="hero-content">
    <h1>Our Teachers</h1>
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

<div class="section-header">
  <div class="section-label">OUR TEACHERS</div>
  <h2>Meet Our Teachers</h2>
</div>

<section class="leadership-section">
  <div class="leadership-grid">
    <article class="leadership-card">
      <div class="leadership-photo-wrap">
        <img src="{{ asset('images/slide-2.png') }}" alt="Coordinator" class="leadership-photo" />
        <div class="leadership-photo-overlay">
          <a href="#" class="leadership-social-btn" title="Twitter" aria-label="Coordinator Twitter">𝕏</a>
          <a href="#" class="leadership-social-btn" title="Facebook" aria-label="Coordinator Facebook">f</a>
          <a href="#" class="leadership-social-btn" title="LinkedIn" aria-label="Coordinator LinkedIn">in</a>
        </div>
      </div>
      <h3 class="leadership-name">Rita Shrestha</h3>
      <p class="leadership-role">Coordinator</p>
    </article>

    <article class="leadership-card leadership-card--center">
      <div class="leadership-photo-wrap">
        <img src="{{ asset('images/slide1.png') }}" alt="Principal" class="leadership-photo" />
        <div class="leadership-photo-overlay">
          <a href="#" class="leadership-social-btn" title="Twitter" aria-label="Principal Twitter">𝕏</a>
          <a href="#" class="leadership-social-btn" title="Facebook" aria-label="Principal Facebook">f</a>
          <a href="#" class="leadership-social-btn" title="LinkedIn" aria-label="Principal LinkedIn">in</a>
        </div>
      </div>
      <h3 class="leadership-name">Suresh Gautam</h3>
      <p class="leadership-role">Principal</p>
    </article>

    <article class="leadership-card">
      <div class="leadership-photo-wrap">
        <img src="{{ asset('images/slide-3.png') }}" alt="Vice Principal" class="leadership-photo" />
        <div class="leadership-photo-overlay">
          <a href="#" class="leadership-social-btn" title="Twitter" aria-label="Vice Principal Twitter">𝕏</a>
          <a href="#" class="leadership-social-btn" title="Facebook" aria-label="Vice Principal Facebook">f</a>
          <a href="#" class="leadership-social-btn" title="LinkedIn" aria-label="Vice Principal LinkedIn">in</a>
        </div>
      </div>
      <h3 class="leadership-name">Mina Karki</h3>
      <p class="leadership-role">Vice Principal</p>
    </article>
  </div>
</section>


<section class="teachers-section">
  <div class="teachers-grid">
    @foreach ($teachers as $teacher)
      <div class="teacher-card">
        <div class="teacher-photo">
          <img
            src="{{ $teacher['img'] }}"
            alt="{{ $teacher['name'] }}"
            loading="lazy"
          />
          <div class="photo-overlay">
            <a href="{{ $teacher['twitter'] }}" class="social-btn" title="Twitter">𝕏</a>
            <a href="{{ $teacher['facebook'] }}" class="social-btn" title="Facebook">f</a>
            <a href="{{ $teacher['linkedin'] }}" class="social-btn" title="LinkedIn">in</a>
          </div>
        </div>
        <div class="teacher-name">{{ $teacher['name'] }}</div>
        <div class="teacher-role">{{ $teacher['role'] }}</div>
      </div>
    @endforeach
  </div>
</section>

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
