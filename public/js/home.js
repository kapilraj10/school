const initMobileNav = () => {
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  const navToggleIcon = navToggle?.querySelector('i');

  if (!navToggle || !navLinks) {
    return;
  }

  const setToggleVisualState = (isOpen) => {
    navToggle.classList.toggle('is-open', isOpen);
    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    if (navToggleIcon) {
      navToggleIcon.classList.toggle('fa-bars', !isOpen);
      navToggleIcon.classList.toggle('fa-times', isOpen);
    }
  };

  const closeMenu = () => {
    navLinks.classList.remove('open');
    setToggleVisualState(false);
  };

  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    setToggleVisualState(isOpen);
  });

  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', (event) => {
    if (!navLinks.contains(event.target) && !navToggle.contains(event.target)) {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
      closeMenu();
    }
  });

  setToggleVisualState(false);
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMobileNav);
} else {
  initMobileNav();
}

document.addEventListener('DOMContentLoaded', () => {
  let current = 0;
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');

  if (slides.length > 0 && dots.length > 0) {
    window.goToSlide = (n) => {
      slides[current].classList.remove('active');
      dots[current].classList.remove('active');
      current = n;
      slides[current].classList.add('active');
      dots[current].classList.add('active');
    };

    setInterval(() => window.goToSlide((current + 1) % slides.length), 5000);
  }

  function animateCounters() {
    document.querySelectorAll('.stat-num').forEach((el) => {
      const target = Number(el.dataset.target);
      const step = Math.ceil(target / 60);
      let val = 0;
      const timer = setInterval(() => {
        val = Math.min(val + step, target);
        el.textContent = `${val.toLocaleString()}+`;

        if (val >= target) {
          clearInterval(timer);
        }
      }, 30);
    });
  }

  const statsSection = document.querySelector('.stats');

  if (statsSection) {
    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting) {
        animateCounters();
        observer.disconnect();
      }
    }, { threshold: 0.3 });

    observer.observe(statsSection);
  }

  const revealEls = document.querySelectorAll('[data-reveal]');

  if (revealEls.length > 0) {
    revealEls.forEach((el) => {
      const dir = el.getAttribute('data-reveal');
      el.style.opacity = '0';
      el.style.transform = dir === 'left' ? 'translateX(-50px)' : 'translateX(50px)';
      el.style.transition = 'opacity .7s ease, transform .7s ease';
    });

    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateX(0)';
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.18 });

    revealEls.forEach((el) => revealObserver.observe(el));
  }

  const checklist = document.querySelector('#aboutChecklist');

  if (checklist) {
    const listObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const items = entry.target.querySelectorAll('li');

          items.forEach((li, index) => {
            setTimeout(() => li.classList.add('revealed'), index * 150);
          });

          listObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    listObserver.observe(checklist);
  }

  const aboutCounters = document.querySelectorAll('.about-stat-num[data-target]');

  if (aboutCounters.length > 0) {
    const aboutCounterObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        const el = entry.target;
        const target = Number(el.dataset.target);
        const suffix = target === 98 ? '%' : '+';
        const step = Math.ceil(target / 60);
        let val = 0;

        const timer = setInterval(() => {
          val = Math.min(val + step, target);
          el.textContent = `${val.toLocaleString()}${suffix}`;

          if (val >= target) {
            clearInterval(timer);
          }
        }, 28);

        aboutCounterObserver.unobserve(el);
      });
    }, { threshold: 0.5 });

    aboutCounters.forEach((counter) => aboutCounterObserver.observe(counter));
  }

  const testimonialTrack = document.getElementById('testimonialSliderTrack');
  const testimonialDots = document.querySelectorAll('.testimonial-dot');
  const testimonialPrevBtn = document.getElementById('testimonialPrevBtn');
  const testimonialNextBtn = document.getElementById('testimonialNextBtn');
  const testimonialOuter = document.getElementById('testimonialSliderOuter');

  if (testimonialTrack && testimonialDots.length > 0) {
    let testimonialCurrent = 0;
    let testimonialTimer = null;
    const testimonialTotal = testimonialDots.length;
    const testimonialInterval = 4000;

    const goToTestimonial = (index) => {
      testimonialCurrent = (index + testimonialTotal) % testimonialTotal;
      testimonialTrack.style.transform = `translateX(-${testimonialCurrent * 100}%)`;
      testimonialDots.forEach((dot, dotIndex) => {
        dot.classList.toggle('active', dotIndex === testimonialCurrent);
      });
    };

    const startTestimonialAuto = () => {
      if (testimonialTimer) {
        clearInterval(testimonialTimer);
      }

      testimonialTimer = setInterval(() => {
        goToTestimonial(testimonialCurrent + 1);
      }, testimonialInterval);
    };

    const stopTestimonialAuto = () => {
      if (testimonialTimer) {
        clearInterval(testimonialTimer);
      }
    };

    testimonialPrevBtn?.addEventListener('click', () => {
      goToTestimonial(testimonialCurrent - 1);
      startTestimonialAuto();
    });

    testimonialNextBtn?.addEventListener('click', () => {
      goToTestimonial(testimonialCurrent + 1);
      startTestimonialAuto();
    });

    testimonialDots.forEach((dot) => {
      dot.addEventListener('click', () => {
        goToTestimonial(Number(dot.dataset.index));
        startTestimonialAuto();
      });
    });

    if (testimonialOuter) {
      testimonialOuter.addEventListener('mouseenter', stopTestimonialAuto);
      testimonialOuter.addEventListener('mouseleave', startTestimonialAuto);

      let touchStartX = 0;
      testimonialOuter.addEventListener('touchstart', (event) => {
        touchStartX = event.touches[0].clientX;
      }, { passive: true });

      testimonialOuter.addEventListener('touchend', (event) => {
        const diff = touchStartX - event.changedTouches[0].clientX;

        if (Math.abs(diff) > 50) {
          goToTestimonial(diff > 0 ? testimonialCurrent + 1 : testimonialCurrent - 1);
          startTestimonialAuto();
        }
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        goToTestimonial(testimonialCurrent - 1);
        startTestimonialAuto();
      }

      if (event.key === 'ArrowRight') {
        goToTestimonial(testimonialCurrent + 1);
        startTestimonialAuto();
      }
    });

    goToTestimonial(0);
    startTestimonialAuto();
  }

  const eventPopupOverlay = document.getElementById('eventPopupOverlay');
  const eventPopupClose = document.getElementById('eventPopupClose');

  if (eventPopupOverlay?.dataset.popupEnabled === '1') {
    requestAnimationFrame(() => {
      eventPopupOverlay.classList.add('active');
    });

    const closePopup = () => {
      eventPopupOverlay.classList.remove('active');
    };

    eventPopupClose?.addEventListener('click', closePopup);

    eventPopupOverlay.addEventListener('click', (event) => {
      if (event.target === eventPopupOverlay) {
        closePopup();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && eventPopupOverlay.classList.contains('active')) {
        closePopup();
      }
    });
  }
});
