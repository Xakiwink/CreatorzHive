(function () {
  var prefersReducedMotion =
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function initScrollReveal() {
    var items = document.querySelectorAll('.reveal');
    if (!items.length) return;

    if (prefersReducedMotion || typeof IntersectionObserver === 'undefined') {
      items.forEach(function (el) {
        el.classList.add('is-visible');
      });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
    );

    items.forEach(function (el) {
      observer.observe(el);
    });
  }

  function formatCount(value, decimals) {
    return decimals > 0 ? value.toFixed(decimals) : String(Math.round(value));
  }

  function animateCount(el) {
    var raw = el.getAttribute('data-count-to') || '0';
    var target = Number(raw);
    if (!isFinite(target)) return;
    var suffix = el.getAttribute('data-suffix') || '';
    var decimals = (raw.split('.')[1] || '').length;

    if (prefersReducedMotion) {
      el.textContent = formatCount(target, decimals) + suffix;
      return;
    }

    var duration = 1200;
    var start = null;

    function tick(now) {
      if (start === null) start = now;
      var progress = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = formatCount(target * eased, decimals) + suffix;
      if (progress < 1) {
        requestAnimationFrame(tick);
      }
    }

    requestAnimationFrame(tick);
  }

  function initCounters() {
    var counters = document.querySelectorAll('[data-count-to]');
    if (!counters.length) return;

    if (typeof IntersectionObserver === 'undefined') {
      counters.forEach(animateCount);
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCount(entry.target);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.4 }
    );

    counters.forEach(function (el) {
      observer.observe(el);
    });
  }

  function boot() {
    initScrollReveal();
    initCounters();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
