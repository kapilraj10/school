document.addEventListener('DOMContentLoaded', () => {
  const images = Array.isArray(window.galleryImages) ? window.galleryImages : [];
  const lightbox = document.getElementById('lightbox');
  const lightboxImage = document.getElementById('lb-img');
  const closeButton = document.getElementById('lightbox-close');
  const prevButton = document.getElementById('lightbox-prev');
  const nextButton = document.getElementById('lightbox-next');
  const items = document.querySelectorAll('.gallery-item');

  if (!lightbox || !lightboxImage || !images.length) {
    return;
  }

  let current = 0;

  function render(index) {
    const selected = images[index];
    lightboxImage.src = selected.img;
    lightboxImage.alt = selected.caption;
  }

  function open(index) {
    current = index;
    render(current);
    lightbox.classList.add('open');
  }

  function close() {
    lightbox.classList.remove('open');
  }

  function shift(direction) {
    current = (current + direction + images.length) % images.length;
    render(current);
  }

  items.forEach((item) => {
    item.addEventListener('click', () => {
      const index = Number(item.dataset.index);

      if (!Number.isNaN(index)) {
        open(index);
      }
    });
  });

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
