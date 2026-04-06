@php
  $aboutSection = [
    'label' => 'LEARN ABOUT US',
    'title' => 'Best School For Your Kids',
    'text' => 'Invidunt lorem justo sanctus clita. Erat lorem labore ea, justo dolor lorem ipsum ut sed eos, ipsum et dolor kasd sit ea justo. Erat justo sed sed diam. Ea et erat ut sed diam sea ipsum est dolor.',
    'points' => [
      'Labore eos amet dolor amet diam',
      'Etsea et sit dolor amet ipsum',
      'Diam dolor diam elitripsum vero.',
    ],
    'main_img' => asset('images/slide1.png'),
    'thumb_img' => asset('images/slide-2.png'),
    'btn_text' => 'Learn More',
    'btn_link' => route('about'),
  ];

  $testimonials = [
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'Ram Prasad Sharma',
      'role' => 'Engineer',
      'img' => asset('images/slide1.png'),
      'stars' => 5,
    ],
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'Sunita Thapa',
      'role' => 'Doctor',
      'img' => asset('images/slide-2.png'),
      'stars' => 5,
    ],
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'David Wilson',
      'role' => 'Banker',
      'img' => asset('images/slide-3.png'),
      'stars' => 4,
    ],
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'Priya Adhikari',
      'role' => 'Journalist',
      'img' => asset('images/logo.jpg'),
      'stars' => 5,
    ],
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'Bikash Gurung',
      'role' => 'Architect',
      'img' => asset('images/inerpageslider.jpg'),
      'stars' => 5,
    ],
    [
      'quote' => 'Sed ea amet kasd elitr stet, stet rebum et ipsum est duo elitr eirmod clita lorem. Dolor tempor ipsum clita lorem sanctus.',
      'name' => 'Anita Rai',
      'role' => 'Teacher',
      'img' => asset('images/slide1.png'),
      'stars' => 4,
    ],
  ];

  $testimonialGroups = array_chunk($testimonials, 3);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Yumak Buddha Mandal School</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Nunito:wght@400;600;700;800&family=Raleway:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
  <link rel="stylesheet" href="{{ asset('css/home.css') }}">
  <script src="{{ asset('js/home.js') }}" defer></script>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="contact">
    <span><i class="fa fa-phone"></i> Call us : +977 01-5523144</span>
    <span><i class="fa fa-envelope"></i> Email : info@ybms.com</span>
  </div>
  <div class="hours">
    <i class="fa fa-clock"></i> Sun - Fri : 09:00 am - 05:30 pm
  </div>
</div>

<!-- NAVBAR -->
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
    <li><a href="{{ route('home') }}" class="active">Home</a></li>
  <li><a href="{{ route('about') }}">About Us</a></li>
    <li><a href="{{ route('blog') }}">Blog</a></li>
  <li><a href="{{ route('staff') }}">Staff</a></li>
  <li><a href="{{ route('gallery') }}">Gallery</a></li>
    <li><a href="{{ route('contact') }}">Contact Us</a></li>
  </ul>
  <button class="nav-search"><i class="fa fa-search"></i></button>
</nav>

<!-- HERO SLIDER -->
<div class="slider" id="slider">
  <div class="slide slide-1 active">
    <div class="slide-content">
      <p class="slide-subtitle">Quality School Education</p>
      <h1 class="slide-title">Welcome to<br>Yumak Bauddha Mandal School</h1>
      <p class="slide-desc">Admissions open for students from Nursery to Class 10.</p>
      <a href="#" class="btn-gold">Read More</a>
    </div>
  </div>
  <div class="slide slide-2">
    <div class="slide-content">
      <p class="slide-subtitle">Learning with Values</p>
      <h1 class="slide-title">Strong Foundation<br>for Every Child</h1>
      <p class="slide-desc">We nurture young minds with modern teaching from Nursery to Class 10.</p>
      <a href="#" class="btn-gold">Read More</a>
    </div>
  </div>
  <div class="slide slide-3">
    <div class="slide-content">
      <p class="slide-subtitle">A Better Future Starts Here</p>
      <h1 class="slide-title">Grow, Learn,<br>and Succeed</h1>
      <p class="slide-desc">Join Yumak Bauddha Mandal School and build a bright future from the early years.</p>
      <a href="#" class="btn-gold">Read More</a>
    </div>
  </div>

  <!-- Slider Dots -->
  <div class="slider-dots">
    <div class="dot active" onclick="goToSlide(0)"></div>
    <div class="dot" onclick="goToSlide(1)"></div>
    <div class="dot" onclick="goToSlide(2)"></div>
  </div>
</div>

<!-- HERO FEATURES -->
<section class="hero-features">
  <div class="hero-features-grid">
    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-joystick"></i></div>
      <h3>Play Ground</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>

    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-music-note-beamed"></i></div>
      <h3>Music and Dance</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>

    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-palette-fill"></i></div>
      <h3>Arts and Crafts</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>

    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-bus-front-fill"></i></div>
      <h3>Safe Transportation</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>

    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-cup-straw"></i></div>
      <h3>Healthy food</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>

    <article class="hero-feature-card">
      <div class="hero-feature-icon"><i class="bi bi-airplane-fill"></i></div>
      <h3>Educational Tour</h3>
      <p>Kasd labore kasd et dolor est rebum dolor ut, clita dolor vero lorem amet elitr vero...</p>
    </article>
  </div>
</section>


<!-- ABOUT SECTION -->
<section class="about-section" id="about">
  <div class="about-grid">
    <div class="about-image-wrap" data-reveal="left">
      <img
        src="{{ $aboutSection['main_img'] }}"
        alt="Kids at school"
        class="about-main-img"
      />
      <div class="exp-badge">
        <div class="num">25+</div>
        <div class="lbl">Years<br>Experience</div>
      </div>
    </div>

    <div class="about-content" data-reveal="right">
      <div class="about-label">{{ $aboutSection['label'] }}</div>

      <h2 class="about-title">
        {!! str_replace('Kids', '<span>Kids</span>', e($aboutSection['title'])) !!}
      </h2>

      <p class="about-text">{{ $aboutSection['text'] }}</p>

      <div class="about-features">
        <img
          src="{{ $aboutSection['thumb_img'] }}"
          alt="Students studying"
          class="about-thumb"
        />
        <ul class="checklist" id="aboutChecklist">
          @foreach ($aboutSection['points'] as $point)
            <li>
              <span class="check-icon"><i class="fa fa-check"></i></span>
              {{ $point }}
            </li>
          @endforeach
        </ul>
      </div>

      <div class="stats-row">
        <div class="stat-box">
          <div class="about-stat-num" data-target="1200">0</div>
          <div class="stat-lbl">Students</div>
        </div>
        <div class="stat-box">
          <div class="about-stat-num" data-target="45">0</div>
          <div class="stat-lbl">Teachers</div>
        </div>
        <div class="stat-box">
          <div class="about-stat-num" data-target="98">0</div>
          <div class="stat-lbl">Pass Rate %</div>
        </div>
      </div>

      <a href="{{ $aboutSection['btn_link'] }}" class="btn-learn">
        {{ $aboutSection['btn_text'] }}
        <i class="fa fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- TESTIMONIAL SECTION -->
<section class="testimonial-section" id="testimonials">
  <div class="testimonial-head">
    <div class="testimonial-section-label">TESTIMONIAL</div>
    <h2>What <span>Parents</span> Say!</h2>
  </div>

  <div class="testimonial-slider-outer" id="testimonialSliderOuter">
    <button class="testimonial-slider-arrow prev" id="testimonialPrevBtn" aria-label="Previous" type="button">
      <i class="fa-solid fa-chevron-left"></i>
    </button>

    <div class="testimonial-slider-track" id="testimonialSliderTrack">
      @foreach ($testimonialGroups as $group)
        <div class="testimonial-slide-group">
          @foreach ($group as $testimonial)
            <div class="testimonial-card-item">
              <div class="testimonial-quote-box">
                <span class="testimonial-quote-mark">&ldquo;</span>
                <p class="testimonial-quote-text">{{ $testimonial['quote'] }}</p>
                <div class="testimonial-stars">
                  @for ($star = 1; $star <= 5; $star++)
                    @if ($star <= $testimonial['stars'])
                      <i class="fa fa-star"></i>
                    @else
                      <i class="fa fa-star" style="opacity:.35"></i>
                    @endif
                  @endfor
                </div>
              </div>

              <div class="testimonial-author-row">
                <img src="{{ $testimonial['img'] }}" alt="{{ $testimonial['name'] }}" class="testimonial-author-img" />
                <div>
                  <div class="testimonial-author-name">{{ $testimonial['name'] }}</div>
                  <div class="testimonial-author-role">{{ $testimonial['role'] }}</div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>

    <button class="testimonial-slider-arrow next" id="testimonialNextBtn" aria-label="Next" type="button">
      <i class="fa-solid fa-chevron-right"></i>
    </button>
  </div>

  <div class="testimonial-dots" id="testimonialDotsWrap">
    @foreach ($testimonialGroups as $groupIndex => $group)
      <button
        class="testimonial-dot {{ $groupIndex === 0 ? 'active' : '' }}"
        data-index="{{ $groupIndex }}"
        aria-label="Slide {{ $groupIndex + 1 }}"
        type="button"></button>
    @endforeach
  </div>
</section>

<!-- STATS -->
<section class="stats">
  <div class="stats-grid">
    <div>
      <div class="stat-num" data-target="1250">0</div>
      <div class="stat-label">Students Enrolled</div>
    </div>
    <div>
      <div class="stat-num" data-target="85">0</div>
      <div class="stat-label">Expert Faculty</div>
    </div>
    <div>
      <div class="stat-num" data-target="48">0</div>
      <div class="stat-label">Courses Available</div>
    </div>
    <div>
      <div class="stat-num" data-target="20">0</div>
      <div class="stat-label">Years of Excellence</div>
    </div>
  </div>
</section>

<!-- EVENTS -->
<section>
  <div class="section-title">
    <h2>Upcoming Events</h2>
    <div class="underline"></div>
    <p>Stay updated with the latest happenings at our university</p>
  </div>
  <div class="events-list" style="max-width:720px;margin:0 auto;">
    <div class="event-item">
      <div class="event-date"><div class="day">15</div><div class="mon">Apr</div></div>
      <div class="event-info">
        <h4>Annual Science & Technology Symposium</h4>
        <p>Join leading researchers and students to discuss the future of technology and innovation.</p>
        <div class="meta"><i class="fa fa-map-marker-alt"></i> Main Auditorium &nbsp;|&nbsp; <i class="fa fa-clock"></i> 9:00 AM – 5:00 PM</div>
      </div>
    </div>
    <div class="event-item">
      <div class="event-date"><div class="day">22</div><div class="mon">Apr</div></div>
      <div class="event-info">
        <h4>Open Campus Day for Prospective Students</h4>
        <p>Tour our facilities, meet faculty, and learn about admission requirements.</p>
        <div class="meta"><i class="fa fa-map-marker-alt"></i> Campus Grounds &nbsp;|&nbsp; <i class="fa fa-clock"></i> 10:00 AM – 2:00 PM</div>
      </div>
    </div>
    <div class="event-item">
      <div class="event-date"><div class="day">05</div><div class="mon">May</div></div>
      <div class="event-info">
        <h4>Graduation Ceremony 2026</h4>
        <p>Celebrating the achievements of our graduating class of 2026.</p>
        <div class="meta"><i class="fa fa-map-marker-alt"></i> University Stadium &nbsp;|&nbsp; <i class="fa fa-clock"></i> 4:00 PM</div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
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
