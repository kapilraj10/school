document.addEventListener('DOMContentLoaded', () => {
  const fills = document.querySelectorAll('.bar-fill');

  if (!fills.length) {
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.width = `${entry.target.dataset.pct}%`;
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  fills.forEach((fill) => observer.observe(fill));
});
