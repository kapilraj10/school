@php
    $site = [
        'name' => 'YUMAK BAUDDHA MANDAL SCHOOL',
        'tagline' => 'Nursery to Class 10',
        'phone' => '+977 01-5523144',
        'email' => 'info@ybms.com',
        'hours' => 'Sun - Fri : 09:00 am - 05:30 pm',
    ];

  $navLinks = ['Home', 'About Us', 'Blog', 'Our Team', 'Gallery', 'Contact Us'];
  $activeNav = 'Our Team';
    $breadcrumb = ['Home', 'Our Teachers'];

  $roleLabels = [
    'principal' => 'Principal',
    'vice_principal' => 'Vice Principal',
    'coordinator' => 'Coordinator',
    'teacher' => 'Teacher',
  ];

  $resolveImageUrl = static function (?string $path, string $fallback): string {
    if (blank($path)) {
      return $fallback;
    }

    if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
      return $path;
    }

    return asset('storage/'.$path);
  };

  $formatSocialUrl = static function (?string $url): ?string {
    if (blank($url)) {
      return null;
    }

    if (
      \Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])
    ) {
      return $url;
    }

    return 'https://'.$url;
  };

  $teacherRecords = \App\Models\Teacher::query()
    ->active()
    ->with('subjects:id,name')
    ->orderBy('name')
    ->get();

  $leadershipFallbacks = [
    [
      'role' => 'principal',
      'name' => 'Suresh Gautam',
      'display_role' => 'Principal',
      'img' => asset('images/slide1.png'),
      'twitter' => '#',
      'facebook' => '#',
      'linkedin' => '#',
    ],
    [
      'role' => 'vice_principal',
      'name' => 'Mina Karki',
      'display_role' => 'Vice Principal',
      'img' => asset('images/slide-3.png'),
      'twitter' => '#',
      'facebook' => '#',
      'linkedin' => '#',
    ],
    [
      'role' => 'coordinator',
      'name' => 'Rita Shrestha',
      'display_role' => 'Coordinator',
      'img' => asset('images/slide-2.png'),
      'twitter' => '#',
      'facebook' => '#',
      'linkedin' => '#',
    ],
  ];

  $leadershipMembers = collect($leadershipFallbacks)
    ->map(function (array $fallback) use ($teacherRecords, $roleLabels, $resolveImageUrl, $formatSocialUrl): array {
      $record = $teacherRecords->firstWhere('profile_role', $fallback['role']);

      if (! $record) {
        return $fallback;
      }

      return [
        'role' => $fallback['role'],
        'name' => $record->name,
        'display_role' => $roleLabels[$record->profile_role] ?? 'Teacher',
        'img' => $resolveImageUrl($record->profile_image, $fallback['img']),
        'twitter' => $formatSocialUrl($record->twitter_url),
        'facebook' => $formatSocialUrl($record->facebook_url),
        'linkedin' => $formatSocialUrl($record->linkedin_url),
      ];
    })
    ->values()
    ->all();

  $teachers = $teacherRecords
    ->filter(function (\App\Models\Teacher $teacher): bool {
      return ! in_array($teacher->profile_role, ['principal', 'vice_principal', 'coordinator'], true);
    })
    ->map(function (\App\Models\Teacher $teacher) use ($resolveImageUrl, $formatSocialUrl): array {
      $primarySubject = $teacher->subjects->first()?->name;

      return [
        'name' => $teacher->name,
        'role' => $primarySubject ? $primarySubject.' Teacher' : 'Teacher',
        'img' => $resolveImageUrl($teacher->profile_image, asset('images/slide1.png')),
        'twitter' => $formatSocialUrl($teacher->twitter_url),
        'facebook' => $formatSocialUrl($teacher->facebook_url),
        'linkedin' => $formatSocialUrl($teacher->linkedin_url),
      ];
    })
    ->values()
    ->all();

  if ($teachers === []) {
    $teachers = [
  ['name' => 'Aarati Sharma', 'role' => 'English Teacher', 'img' => asset('images/slide1.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Bikash Thapa', 'role' => 'Science Teacher', 'img' => asset('images/slide-2.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Nima Lama', 'role' => 'Math Teacher', 'img' => asset('images/slide-3.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Sujata Karki', 'role' => 'Computer Teacher', 'img' => asset('images/logo.jpg'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Dipesh Rai', 'role' => 'Social Teacher', 'img' => asset('images/inerpageslider.jpg'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Mina Gurung', 'role' => 'Nepali Teacher', 'img' => asset('images/slide1.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Rohan Shrestha', 'role' => 'Art Teacher', 'img' => asset('images/slide-2.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
  ['name' => 'Pooja Adhikari', 'role' => 'Music Teacher', 'img' => asset('images/slide-3.png'), 'twitter' => null, 'facebook' => null, 'linkedin' => null],
    ];
  }
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
    } elseif ($link === 'Our Team') {
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
    @foreach ($leadershipMembers as $leader)
    <article class="leadership-card {{ $leader['role'] === 'principal' ? 'leadership-card--center' : '' }}">
      <div class="leadership-photo-wrap">
        <img src="{{ $leader['img'] }}" alt="{{ $leader['display_role'] }}" class="leadership-photo" />
        <div class="leadership-photo-overlay">
          @if ($leader['twitter'])
            <a href="{{ $leader['twitter'] }}" class="leadership-social-btn" title="Twitter" aria-label="{{ $leader['display_role'] }} Twitter" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
          @endif
          @if ($leader['facebook'])
            <a href="{{ $leader['facebook'] }}" class="leadership-social-btn" title="Facebook" aria-label="{{ $leader['display_role'] }} Facebook" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
          @endif
          @if ($leader['linkedin'])
            <a href="{{ $leader['linkedin'] }}" class="leadership-social-btn" title="LinkedIn" aria-label="{{ $leader['display_role'] }} LinkedIn" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
          @endif
        </div>
      </div>
      <h3 class="leadership-name">{{ $leader['name'] }}</h3>
      <p class="leadership-role">{{ $leader['display_role'] }}</p>
    </article>
    @endforeach
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
            @if ($teacher['twitter'])
              <a href="{{ $teacher['twitter'] }}" class="social-btn" title="Twitter" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
            @endif
            @if ($teacher['facebook'])
              <a href="{{ $teacher['facebook'] }}" class="social-btn" title="Facebook" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
            @endif
            @if ($teacher['linkedin'])
              <a href="{{ $teacher['linkedin'] }}" class="social-btn" title="LinkedIn" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
            @endif
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

</body>
</html>
