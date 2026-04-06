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
});
