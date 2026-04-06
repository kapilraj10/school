@php
  $site = [
    'phone' => '+977 01-5523144',
    'email' => 'info@ybms.com',
    'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
  ];
  $navLinks = ['Home', 'About Us', 'Blog', 'Staff', 'Gallery', 'Contact Us'];
  $activeNav = 'Contact Us';

  $infoCards = [
    [
      'icon' => 'fa-map-marker-alt',
      'title' => 'Address',
      'text' => 'Yumak Bauddha Mandal School, Kathmandu, Nepal',
      'isLink' => false,
      'href' => '#',
    ],
    [
      'icon' => 'fa-envelope',
      'title' => 'Email',
      'text' => 'info@ybms.com',
      'isLink' => true,
      'href' => 'mailto:info@ybms.com',
    ],
    [
      'icon' => 'fa-phone-alt',
      'title' => 'Phone',
      'text' => '+977 01-5523144',
      'isLink' => true,
      'href' => 'tel:+977015523144',
    ],
  ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us - Yumak Bauddha Mandal School</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Caveat:wght@700&family=Raleway:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="{{ asset('css/home.css') }}">
  <link rel="stylesheet" href="{{ asset('css/contact.css') }}">
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
        } elseif ($link === 'Staff') {
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

<div class="page-header" style="background-image: linear-gradient(rgb(0 0 0 / 45%), rgb(0 0 0 / 45%)), url('{{ asset('images/inerpageslider.jpg') }}');">
  <h1>Contact Us</h1>
  <p class="breadcrumb">
    <a href="{{ route('home') }}">Home</a>
    <span>/</span>
    <span>Contact Us</span>
  </p>
</div>

<div class="contact-section">
  <div class="section-label">GET IN TOUCH</div>
  <div class="section-heading">
    <h2>Contact Us For Any Query</h2>
  </div>
  <p class="section-sub">
    We are happy to hear from you. Please send your message and our school office will respond soon.
  </p>

  <div class="contact-grid">
    <div class="contact-form">
      <div class="alert-success" id="successMessage" hidden>
        <i class="fa fa-check-circle"></i>
        <span>Your message has been sent successfully! We will get back to you soon.</span>
      </div>

      <form method="POST" action="{{ route('contact') }}" novalidate id="contactForm">
        @csrf

        <div class="form-group">
          <input type="text" name="name" placeholder="Your Name" />
          <span class="error-msg" data-error-for="name"></span>
        </div>

        <div class="form-group">
          <input type="email" name="email" placeholder="Your Email" />
          <span class="error-msg" data-error-for="email"></span>
        </div>

        <div class="form-group">
          <input type="text" name="subject" placeholder="Subject" />
          <span class="error-msg" data-error-for="subject"></span>
        </div>

        <div class="form-group">
          <textarea name="message" placeholder="Message"></textarea>
          <span class="error-msg" data-error-for="message"></span>
        </div>

        <button type="submit" class="btn-send">
          <i class="fa fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>

    <div class="info-cards">
      @foreach ($infoCards as $card)
        <div class="info-card">
          <div class="info-icon"><i class="fa {{ $card['icon'] }}"></i></div>
          <div class="info-text">
            <h4>{{ $card['title'] }}</h4>
            @if ($card['isLink'])
              <a href="{{ $card['href'] }}">{{ $card['text'] }}</a>
            @else
              <p>{{ $card['text'] }}</p>
            @endif
          </div>
        </div>
      @endforeach

      <div class="info-card">
        <div class="info-icon"><i class="fa fa-clock"></i></div>
        <div class="info-text">
          <h4>Opening Hours</h4>
          <p><strong>Sunday - Friday:</strong></p>
          <p>08:00 AM - 05:00 PM</p>
        </div>
      </div>
    </div>
  </div>

  <div class="map-wrap">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d56516.31713633893!2d85.29111329985891!3d27.70895594415669!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1854f0f6f5b9%3A0xd5f9f47f5f8f8dc9!2sKathmandu!5e0!3m2!1sen!2snp!4v1680000000000"
      allowfullscreen=""
      loading="lazy">
    </iframe>
  </div>
</div>

<button id="scrollTop" title="Back to top" type="button">
  <i class="fa fa-chevron-up"></i>
</button>

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

<script src="{{ asset('js/contact.js') }}"></script>
</body>
</html>
