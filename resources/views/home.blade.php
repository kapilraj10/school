<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Yumak Buddha Mandal School</title>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
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
  <a href="#" class="logo">
    <img src="{{ asset('images/logo.png') }}" alt="Yumak Bauddha Mandal School logo" class="logo-image">
    <div class="logo-text">
      <strong>Y.B.M.S</strong>
      <span>Nursery to Class 10</span>
    </div>
  </a>
  <ul class="nav-links">
    <li><a href="#" class="active">Home</a></li>
  <li><a href="{{ route('about') }}">About Us</a></li>
    <li><a href="#">Courses</a></li>
    <li><a href="#">Blog</a></li>
    <li><a href="#">Event</a></li>
    <li><a href="#">Staff</a></li>
    <li><a href="#">Gallery</a></li>
    <li><a href="#">Contact Us</a></li>
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

<!-- SEARCH COURSES BAND -->
<div class="search-band">
  <div class="search-box">
    <h3>Search Courses</h3>
    <div class="search-row">
      <select>
        <option>Select Category</option>
        <option>Science</option>
        <option>Arts</option>
        <option>Commerce</option>
        <option>Engineering</option>
      </select>
      <input type="text" placeholder="Search keyword..."/>
      <button class="btn-search"><i class="fa fa-search"></i> Search</button>
    </div>
  </div>
</div>

<!-- COURSES -->
<section style="background:#f5f5f5;">
  <div class="section-title">
    <h2>Popular Courses</h2>
    <div class="underline"></div>
    <p>Explore our wide range of courses taught by expert faculty members</p>
  </div>
  <div class="courses-grid">
    <div class="course-card">
      <img src="https://images.unsplash.com/photo-1532012197267-da84d127e765?w=600&q=80" alt="Course"/>
      <div class="course-card-body">
        <span class="course-tag">Science</span>
        <h4>Bachelor of Computer Science</h4>
        <p>Learn programming, algorithms, AI and software engineering fundamentals.</p>
        <div class="course-meta">
          <span><i class="fa fa-clock"></i> 4 Years</span>
          <span><i class="fa fa-users"></i> 120 Students</span>
          <span><i class="fa fa-star"></i> 4.8</span>
        </div>
      </div>
    </div>
    <div class="course-card">
      <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&q=80" alt="Course"/>
      <div class="course-card-body">
        <span class="course-tag">Commerce</span>
        <h4>Master of Business Administration</h4>
        <p>Develop leadership, strategic thinking and business management skills.</p>
        <div class="course-meta">
          <span><i class="fa fa-clock"></i> 2 Years</span>
          <span><i class="fa fa-users"></i> 80 Students</span>
          <span><i class="fa fa-star"></i> 4.6</span>
        </div>
      </div>
    </div>
    <div class="course-card">
      <img src="https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=600&q=80" alt="Course"/>
      <div class="course-card-body">
        <span class="course-tag">Science</span>
        <h4>Bachelor of Pharmacy</h4>
        <p>Study drug development, pharmacology and pharmaceutical sciences.</p>
        <div class="course-meta">
          <span><i class="fa fa-clock"></i> 4 Years</span>
          <span><i class="fa fa-users"></i> 60 Students</span>
          <span><i class="fa fa-star"></i> 4.7</span>
        </div>
      </div>
    </div>
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
      <h5>About Univercity</h5>
      <p>We are committed to providing world-class education and fostering intellectual growth, innovation, and leadership for the next generation of professionals.</p>
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
        <li><a href="#">Home</a></li>
  <li><a href="{{ route('about') }}">About Us</a></li>
        <li><a href="#">Courses</a></li>
        <li><a href="#">Events</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Contact Us</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Our Courses</h5>
      <ul>
        <li><a href="#">Computer Science</a></li>
        <li><a href="#">Business Admin</a></li>
        <li><a href="#">Engineering</a></li>
        <li><a href="#">Medical Science</a></li>
        <li><a href="#">Arts & Design</a></li>
        <li><a href="#">Law</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Contact Info</h5>
      <ul>
        <li><a href="#"><i class="fa fa-map-marker-alt" style="color:var(--gold);margin-right:8px"></i> 123 University Ave, City</a></li>
        <li><a href="#"><i class="fa fa-phone" style="color:var(--gold);margin-right:8px"></i> +01 123 456</a></li>
        <li><a href="#"><i class="fa fa-envelope" style="color:var(--gold);margin-right:8px"></i> info@info.com</a></li>
        <li><a href="#"><i class="fa fa-clock" style="color:var(--gold);margin-right:8px"></i> Mon–Sat: 9am–5:30pm</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; 2026 Univercity of Education. All Rights Reserved.
  </div>
</footer>

</body>
</html>
