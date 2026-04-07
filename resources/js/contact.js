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

const contactForm = document.getElementById('contactForm');
const successMessage = document.getElementById('successMessage');

if (successMessage !== null && !successMessage.hidden) {
  window.setTimeout(() => {
    successMessage.hidden = true;
  }, 5000);
}

if (contactForm !== null) {
  contactForm.addEventListener('submit', (event) => {
    if (successMessage !== null) {
      successMessage.hidden = true;
    }

    const fields = [
      { name: 'name', label: 'Name' },
      { name: 'email', label: 'Email' },
      { name: 'subject', label: 'Subject' },
      { name: 'message', label: 'Message' },
    ];

    let valid = true;

    fields.forEach((field) => {
      const input = contactForm.elements[field.name];
      const errorElement = contactForm.querySelector(`[data-error-for='${field.name}']`);
      const value = input.value.trim();

      input.classList.remove('error');

      if (errorElement !== null) {
        errorElement.textContent = '';
      }

      if (value === '') {
        valid = false;
        input.classList.add('error');

        if (errorElement !== null) {
          errorElement.textContent = `${field.label} is required.`;
        }
      }

      if (field.name === 'email' && value !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(value)) {
          valid = false;
          input.classList.add('error');

          if (errorElement !== null) {
            errorElement.textContent = 'Please enter a valid email address.';
          }
        }
      }
    });

    if (!valid) {
      event.preventDefault();
    }
  });

  contactForm.querySelectorAll('input, textarea').forEach((element) => {
    element.addEventListener('input', () => {
      element.classList.remove('error');
      if (successMessage !== null) {
        successMessage.hidden = true;
      }
      const errorElement = contactForm.querySelector(`[data-error-for='${element.name}']`);

      if (errorElement !== null) {
        errorElement.textContent = '';
      }
    });
  });
}

const scrollButton = document.getElementById('scrollTop');

if (scrollButton !== null) {
  window.addEventListener('scroll', () => {
    scrollButton.classList.toggle('visible', window.scrollY > 300);
  });

  scrollButton.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

const infoCards = document.querySelectorAll('.info-card');

if (infoCards.length > 0) {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.2 },
  );

  infoCards.forEach((card) => {
    observer.observe(card);
  });
}
