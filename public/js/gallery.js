const initMobileNav = () => {
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');

  if (!navToggle || !navLinks) {
    return;
  }

  const closeMenu = () => {
    navLinks.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  };

  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMobileNav);
} else {
  initMobileNav();
}

document.addEventListener('DOMContentLoaded', () => {
  const images = Array.isArray(window.galleryImages) ? window.galleryImages : [];
  const lightbox = document.getElementById('lightbox');
  const lightboxImage = document.getElementById('lb-img');
  const closeButton = document.getElementById('lightbox-close');
  const prevButton = document.getElementById('lightbox-prev');
  const nextButton = document.getElementById('lightbox-next');
  const items = Array.from(document.querySelectorAll('.gallery-item'));
  const filterButtons = Array.from(document.querySelectorAll('.filter-btn'));

  if (!lightbox || !lightboxImage || !images.length) {
    return;
  }

  let visibleImageIndexes = items.map((item) => Number(item.dataset.index));
  let current = 0;

  function render(index) {
    const selectedImageIndex = visibleImageIndexes[index];
    const selected = images[selectedImageIndex];

    if (!selected) {
      return;
    }

    lightboxImage.src = selected.img;
    lightboxImage.alt = selected.caption;
  }

  function open(index) {
    const mappedIndex = visibleImageIndexes.indexOf(index);

    if (mappedIndex === -1) {
      return;
    }

    current = mappedIndex;
    render(current);
    lightbox.classList.add('open');
  }

  function close() {
    lightbox.classList.remove('open');
  }

  function shift(direction) {
    if (!visibleImageIndexes.length) {
      return;
    }

    current = (current + direction + visibleImageIndexes.length) % visibleImageIndexes.length;
    render(current);
  }

  function applyFilter(category) {
    items.forEach((item) => {
      const itemCategory = item.dataset.category;
      const shouldShow = category === 'all' || itemCategory === category;
      item.classList.toggle('is-hidden', !shouldShow);
    });

    visibleImageIndexes = items
      .filter((item) => !item.classList.contains('is-hidden'))
      .map((item) => Number(item.dataset.index));

    if (lightbox.classList.contains('open') && !visibleImageIndexes.length) {
      close();
    }
  }

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const selectedCategory = button.dataset.category ?? 'all';

      filterButtons.forEach((filterButton) => {
        filterButton.classList.toggle('active', filterButton === button);
      });

      applyFilter(selectedCategory);
    });
  });

  items.forEach((item) => {
    item.addEventListener('click', () => {
      const index = Number(item.dataset.index);

      if (!Number.isNaN(index)) {
        open(index);
      }
    });
  });

  applyFilter('all');

  closeButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    close();
  });

  prevButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    shift(-1);
  });

  nextButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    shift(1);
  });

  lightbox.addEventListener('click', (event) => {
    if (event.target === lightbox) {
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (!lightbox.classList.contains('open')) {
      return;
    }

    if (event.key === 'Escape') {
      close();
    }

    if (event.key === 'ArrowRight') {
      shift(1);
    }

    if (event.key === 'ArrowLeft') {
      shift(-1);
    }
  });
});
