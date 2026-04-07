@php
  $site = [
    'phone' => '+977 01-5523144',
    'email' => 'info@ybms.com',
    'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
  ];
  $navLinks = ['Home', 'About Us', 'Blog', 'Our Team', 'Gallery', 'Contact Us'];
  $activeNav = 'Blog';

  $posts = [
    [
      'id' => 1,
      'title' => 'Students Seek Lake of Communication',
      'slug' => 'students-seek-lake-of-communication',
      'author' => 'Admin',
      'date' => 'Feb 09, 2015',
      'excerpt' => "This is Photoshop's version of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis biben dum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris.",
      'image' => asset('images/slide1.png'),
      'category_icon' => '👥',
    ],
    [
      'id' => 2,
      'title' => 'Students Seek Lake of Communication',
      'slug' => 'students-seek-lake-of-communication-2',
      'author' => 'Admin',
      'date' => 'Feb 09, 2015',
      'excerpt' => "This is Photoshop's version of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis biben dum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris.",
      'image' => asset('images/slide-2.png'),
      'category_icon' => '🎓',
    ],
    [
      'id' => 3,
      'title' => 'Students Seek Lake of Communication',
      'slug' => 'students-seek-lake-of-communication-3',
      'author' => 'Admin',
      'date' => 'Feb 09, 2015',
      'excerpt' => "This is Photoshop's version of Lorem Ipsum. Proin gravida nibh vel velit auctor aliquet. Aenean sollicitudin, lorem quis biben dum auctor, nisi elit consequat ipsum, nec sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet mauris.",
      'image' => asset('images/slide-3.png'),
      'category_icon' => '📚',
    ],
  ];

  $courses = [
    ['name' => 'Politics & History', 'count' => '03'],
    ['name' => 'Journalism', 'count' => '01'],
    ['name' => 'Medical Sciences', 'count' => '03'],
    ['name' => 'Health', 'count' => '01'],
    ['name' => 'Sports', 'count' => '03'],
    ['name' => 'Arts', 'count' => '02'],
    ['name' => 'Tourism & Culture', 'count' => '06'],
  ];

  $popular = [
    [
      'title' => 'Neque Porro Quisquam Est Qui Dolorem Dolor',
      'date' => '19 Dec, 2015',
      'img' => asset('images/slide1.png'),
    ],
    [
      'title' => 'Neque Porro Quisquam Est Qui Dolorem Dolor',
      'date' => '19 Dec, 2015',
      'img' => asset('images/slide-2.png'),
    ],
    [
      'title' => 'Neque Porro Quisquam Est Qui Dolorem Dolor',
      'date' => '19 Dec, 2015',
      'img' => asset('images/slide-3.png'),
    ],
  ];

  $featured = [
    [
      'title' => 'Professional Teaching Course',
      'instructor' => 'Anna Doe',
      'price' => '$99.99',
      'img' => asset('images/slide1.png'),
    ],
    [
      'title' => 'Professional Teaching Course',
      'instructor' => 'Anna Doe',
      'price' => '$99.99',
      'img' => asset('images/slide-2.png'),
    ],
    [
      'title' => 'Professional Teaching Course',
      'instructor' => 'Anna Doe',
      'price' => '$99.99',
      'img' => asset('images/slide-3.png'),
    ],
  ];

  $tags = ['Science', 'Knowledge', 'Courage', 'Sports', 'Impression', 'History & Politics', 'Admission', 'Arts', 'Research', 'Career', 'PHD'];

  $totalPages = 20;
  $currentPage = (int) request()->query('page', 1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Blog Large - Yumak Bauddha Mandal School</title>
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
  <h1>BLOG LARGE</h1>
  <p class="page-subtitle">School Blog</p>
  <p class="breadcrumb">
    <a href="{{ route('home') }}">Home</a> /
    <span>Blog Large</span>
  </p>
</div>

<div class="container blog-container">
  <div class="blog-layout">
    <main>
      @foreach ($posts as $post)
        <article class="post-item">
          <div class="post-thumb">
            <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}"/>
            <div class="cat-badge">{{ $post['category_icon'] }}</div>
          </div>

          <a href="#" class="post-title">{{ strtoupper($post['title']) }}</a>

          <div class="post-meta">
            <span><i class="fa fa-user"></i> {{ $post['author'] }}</span>
            <span><i class="fa fa-clock"></i> {{ $post['date'] }}</span>
          </div>

          <p class="post-excerpt">{{ $post['excerpt'] }}</p>

          <a href="#" class="btn-gold">READ MORE</a>
        </article>
      @endforeach

      
    </main>

    <aside class="sidebar">
      <form class="sidebar-search" id="sidebarSearchForm" action="{{ route('blog') }}" method="GET">
        <input type="text" name="search" id="sidebarSearchInput" placeholder="Search ..." value="{{ request('search', '') }}"/>
        <button type="submit"><i class="fa fa-search"></i></button>
      </form>

      <div class="widget">
        <div class="widget-title"><em>OUR</em> COURSE</div>
        <ul class="course-list">
          @foreach ($courses as $course)
            <li>
              {{ $course['name'] }}
              <span>{{ $course['count'] }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <div class="widget">
        <div class="widget-title"><em>POPULAR</em> COURSES</div>
        @foreach ($popular as $item)
          <div class="popular-item">
            <img src="{{ $item['img'] }}" alt="Popular course"/>
            <div class="popular-info">
              <h5>{{ $item['title'] }}</h5>
              <span class="date">{{ $item['date'] }}</span>
            </div>
          </div>
        @endforeach
      </div>

      <div class="widget">
        <div class="widget-title"><em>WORKING</em> HOURS</div>
        <ul class="hours-list">
          <li><span class="day">Monday</span><span class="time">9:00 am - 5:30 pm</span></li>
          <li><span class="day">Tuesday</span><span class="time">9:00 am - 5:30 pm</span></li>
          <li><span class="day">Wednesday</span><span class="time">9:00 am - 5:30 pm</span></li>
          <li><span class="day">Thursday</span><span class="time">9:00 am - 5:30 pm</span></li>
          <li><span class="day">Friday</span><span class="time">9:00 am - 4:00 pm</span></li>
          <li><span class="day">Saturday</span><span class="time">10:00 am - 2:00 pm</span></li>
          <li><span class="day">Sunday</span><span class="closed">Closed</span></li>
        </ul>
      </div>

      <div class="widget">
        <div class="widget-title"><em>POPULAR</em> COURSES</div>
        @foreach ($featured as $item)
          <div class="featured-item">
            <img src="{{ $item['img'] }}" alt="Featured course"/>
            <div class="featured-info">
              <h5>{{ $item['title'] }}</h5>
              <div class="stars">★★★★★</div>
              <div class="featured-bottom">
                <span class="instructor">{{ $item['instructor'] }}</span>
                <span class="price-badge">{{ $item['price'] }}</span>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="widget">
        <div class="widget-title">TAGS</div>
        <div class="tags-cloud">
          @foreach ($tags as $tag)
            <a href="{{ route('blog', ['tag' => $tag]) }}" class="tag">{{ $tag }}</a>
          @endforeach
        </div>
      </div>
    </aside>
  </div>
</div>

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
  <li><a href="{{ route('staff') }}">Our Team</a></li>
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

<script src="{{ asset('js/blog.js') }}"></script>
</body>
</html>
