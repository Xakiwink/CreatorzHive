(function () {
  'use strict';

  var q = function (sel, root) {
    return (root || document).querySelector(sel);
  };
  var qa = function (sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  };

  /** Front-controller URL with optional query params (subdirectory-safe). */
  function routeQueryUrl(route, extra) {
    var base = typeof window.__BASE_PATH__ === 'string' ? window.__BASE_PATH__ : '';
    var p = new URLSearchParams();
    p.set('route', route);
    if (extra && typeof extra === 'object') {
      Object.keys(extra).forEach(function (k) {
        var v = extra[k];
        if (v !== undefined && v !== null) {
          p.set(k, String(v));
        }
      });
    }
    var qs = p.toString();
    var prefix = base ? base.replace(/\/$/, '') : '';
    if (!prefix) {
      var path = window.location.pathname || '/';
      if (/\/[^/]+\.(php|html)$/i.test(path)) {
        path = path.replace(/\/[^/]+$/, '');
      }
      path = path.replace(/\/$/, '');
      prefix = path === '/' ? '' : path;
    }
    if (prefix === '') {
      return '/?' + qs;
    }
    return prefix + '/?' + qs;
  }

  function postForm(route, data, btn) {
    var token = window.__CSRF__ || '';
    var fd = new URLSearchParams();
    Object.keys(data).forEach(function (k) {
      if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]);
    });
    fd.set('_token', token);
    setBtnLoading(btn, true);
    return fetch(routeQueryUrl(route), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: fd.toString(),
    })
      .then(function (r) {
        return r
          .json()
          .then(function (payload) {
            if (payload && typeof payload === 'object' && payload.status == null) {
              payload.status = r.status;
            }
            return payload;
          })
          .catch(function () {
            return { success: false, message: 'Unexpected response', status: r.status };
          });
      })
      .finally(function () {
        setBtnLoading(btn, false);
      });
  }

  function setBtnLoading(btn, loading) {
    if (!btn) return;
    btn.disabled = !!loading;
    if (loading) btn.classList.add('loading');
    else btn.classList.remove('loading');
  }

  function passwordStrength(password) {
    var s = 0;
    if (!password) return 0;
    if (password.length >= 8) s++;
    if (/[A-Z]/.test(password)) s++;
    if (/[0-9]/.test(password)) s++;
    if (/[^A-Za-z0-9]/.test(password)) s++;
    return s;
  }

  var STRENGTH_LABELS = ['', 'Weak', 'Fair', 'Good', 'Strong'];

  function updateStrengthMeter(meterEl, barEl, labelEl, password) {
    var s = passwordStrength(password);
    if (meterEl) meterEl.setAttribute('data-level', String(s));
    if (barEl) {
      barEl.style.width = s * 25 + '%';
      barEl.style.background =
        s <= 1 ? '#ef4444' : s === 2 ? '#f59e0b' : '#10b981';
    }
    if (labelEl) {
      labelEl.textContent = password ? STRENGTH_LABELS[s] : '';
    }
  }

  function showFieldError(fieldId, message) {
    var el = q('#' + fieldId);
    if (!el) return;
    var wrap = el.closest('.row') || el.parentElement;
    var existing = wrap.querySelector('.error');
    if (!existing) {
      existing = document.createElement('div');
      existing.className = 'error';
      wrap.appendChild(existing);
    }
    existing.textContent = message;
    el.style.borderColor = 'var(--color-danger)';
  }

  function clearFieldError(fieldId) {
    var el = q('#' + fieldId);
    if (!el) return;
    el.style.borderColor = '';
    var wrap = el.closest('.row') || el.parentElement;
    var existing = wrap.querySelector('.error');
    if (existing) existing.remove();
  }

  function clearAllRegisterErrors() {
    ['name', 'username', 'email', 'password', 'password_confirmation', 'terms'].forEach(clearFieldError);
  }

  function bindPasswordToggle(toggleId, inputId) {
    var toggle = q('#' + toggleId);
    var input = q('#' + inputId);
    if (!toggle || !input) return;
    toggle.addEventListener('click', function () {
      var show = input.getAttribute('type') === 'password';
      input.setAttribute('type', show ? 'text' : 'password');
      toggle.textContent = show ? 'Hide' : 'Show';
      toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
      toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  }

  function applyRegisterErrors(errors) {
    if (!errors || typeof errors !== 'object') return;
    Object.keys(errors).forEach(function (field) {
      var msgs = errors[field];
      var msg = Array.isArray(msgs) ? msgs[0] : String(msgs);
      showFieldError(field, msg);
    });
  }

  /* ----- Login ----- */
  var loginForm = q('#loginForm');
  if (loginForm) {
    var remembered = localStorage.getItem('creatorzhive_remember_email');
    if (remembered && q('#email')) q('#email').value = remembered;

    bindPasswordToggle('toggleLoginPassword', 'password');

    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = q('#loginBtn');
      var msgEl = q('#formMsg');
      if (msgEl) {
        msgEl.textContent = '';
        msgEl.className = 'auth-msg';
      }
      var fd = new FormData(loginForm);
      var data = {};
      fd.forEach(function (v, k) {
        data[k] = v;
      });
      postForm('login', data, btn).then(function (res) {
        if (msgEl) {
          msgEl.textContent = res.message || '';
          msgEl.classList.add(res.success ? 'auth-msg--success' : 'auth-msg--error');
        }
        if (res.success && res.data && res.data.redirect) {
          if (q('#rememberMe') && q('#rememberMe').checked && q('#email')) {
            localStorage.setItem('creatorzhive_remember_email', q('#email').value.trim());
          } else {
            localStorage.removeItem('creatorzhive_remember_email');
          }
          window.location.href = res.data.redirect;
        }
      });
    });
  }

  /* ----- Register ----- */
  var registerForm = q('#registerForm');
  if (registerForm) {
    bindPasswordToggle('toggleRegPassword', 'password');
    bindPasswordToggle('toggleRegPassword2', 'password_confirmation');

    var strengthMeter = q('#strengthMeter');
    var strengthBar = q('#strengthBar');
    var strengthLabel = q('#strengthLabel');
    var passInput = q('#password');
    if (passInput) {
      passInput.addEventListener('input', function () {
        updateStrengthMeter(strengthMeter, strengthBar, strengthLabel, passInput.value);
      });
    }

    var googleRegisterBtn = q('#googleRegisterBtn');
    function syncGoogleRegisterHref() {
      if (!googleRegisterBtn) return;
      var roleEl = q('#role');
      var role = roleEl ? roleEl.value || 'creator' : 'creator';
      googleRegisterBtn.href = routeQueryUrl('google-auth', { role: role });
    }
    syncGoogleRegisterHref();

    qa('.role-btn').forEach(function (b) {
      b.addEventListener('click', function () {
        qa('.role-btn').forEach(function (x) {
          x.classList.remove('active');
        });
        b.classList.add('active');
        var role = q('#role');
        if (role) role.value = b.getAttribute('data-role') || 'creator';
        syncGoogleRegisterHref();
      });
    });

    var checkTimer = null;
    var userInput = q('#username');
    if (userInput) {
      userInput.addEventListener('input', function () {
        clearFieldError('username');
        clearTimeout(checkTimer);
        checkTimer = setTimeout(function () {
          var u = userInput.value.trim();
          if (u.length < 3) return;
          fetch(routeQueryUrl('check_username', { username: u }), {
            headers: { Accept: 'application/json' },
          })
            .then(function (r) {
              return r.json();
            })
            .then(function (res) {
              if (res.success && res.data && res.data.available === false) {
                showFieldError('username', 'Username is already taken');
              }
            })
            .catch(function () {});
        }, 400);
      });
    }

    ['name', 'email', 'password', 'password_confirmation'].forEach(function (id) {
      var inp = q('#' + id);
      if (inp) inp.addEventListener('input', function () {
        clearFieldError(id);
      });
    });
    var termsCb = q('#terms');
    if (termsCb) {
      termsCb.addEventListener('change', function () {
        clearFieldError('terms');
      });
    }

    registerForm.addEventListener('submit', function (e) {
      e.preventDefault();
      clearAllRegisterErrors();
      var btn = q('#registerBtn');
      var msgEl = q('#formMsg');
      if (msgEl) {
        msgEl.textContent = '';
        msgEl.className = 'auth-msg';
      }
      var fd = new FormData(registerForm);
      var data = {};
      fd.forEach(function (v, k) {
        data[k] = v;
      });
      if (termsCb && !termsCb.checked) {
        showFieldError('terms', 'You must accept the terms to continue');
        return;
      }
      postForm('register', data, btn).then(function (res) {
        if (res.success) {
          var panel = q('#registerFormPanel');
          var okPanel = q('#registerSuccessPanel');
          if (panel) panel.classList.add('hidden');
          if (okPanel) okPanel.classList.remove('hidden');
          if (msgEl) msgEl.textContent = '';
          return;
        }
        if (msgEl) {
          msgEl.textContent = res.message || 'Something went wrong';
          msgEl.classList.add('auth-msg--error');
        }
        applyRegisterErrors(res.errors);
      });
    });
  }

  /* ----- Forgot ----- */
  var forgotForm = q('#forgotForm');
  if (forgotForm) {
    var FORGOT_COOLDOWN_SECONDS = 60;
    var cooldownTimer = null;
    var cooldownKey = 'creatorzhive_forgot_otp_cooldown_until';

    function setForgotButtonCooldown(secondsLeft) {
      var btn = q('#forgotBtn');
      if (!btn) return;
      var label = btn.querySelector('.btn-label');
      if (secondsLeft > 0) {
        btn.disabled = true;
        if (label) label.textContent = 'Resend OTP in ' + secondsLeft + 's';
      } else {
        btn.disabled = false;
        if (label) label.textContent = 'Send OTP code';
      }
    }

    function startForgotCooldown(secondsLeft) {
      var left = Math.max(0, secondsLeft | 0);
      if (cooldownTimer) {
        clearInterval(cooldownTimer);
        cooldownTimer = null;
      }
      setForgotButtonCooldown(left);
      if (left <= 0) return;
      cooldownTimer = setInterval(function () {
        left -= 1;
        setForgotButtonCooldown(left);
        if (left <= 0) {
          clearInterval(cooldownTimer);
          cooldownTimer = null;
          try {
            localStorage.removeItem(cooldownKey);
          } catch (e) {}
        }
      }, 1000);
    }

    function restoreForgotCooldown() {
      try {
        var untilRaw = localStorage.getItem(cooldownKey);
        if (!untilRaw) return;
        var until = parseInt(untilRaw, 10);
        if (!Number.isFinite(until)) return;
        var now = Date.now();
        var msLeft = until - now;
        if (msLeft <= 0) {
          localStorage.removeItem(cooldownKey);
          return;
        }
        startForgotCooldown(Math.ceil(msLeft / 1000));
      } catch (e) {}
    }

    restoreForgotCooldown();

    forgotForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = q('#forgotBtn');
      var msgEl = q('#formMsg');
      if (msgEl) {
        msgEl.textContent = '';
        msgEl.className = 'auth-msg';
      }
      var fd = new FormData(forgotForm);
      var data = {};
      fd.forEach(function (v, k) {
        data[k] = v;
      });
      postForm('forgot-password', data, btn).then(function (res) {
        if (res.success) {
          var until = Date.now() + FORGOT_COOLDOWN_SECONDS * 1000;
          try {
            localStorage.setItem(cooldownKey, String(until));
          } catch (err) {}
          startForgotCooldown(FORGOT_COOLDOWN_SECONDS);
          if (msgEl) {
            msgEl.textContent = res.message || 'OTP sent. Please check your inbox.';
            msgEl.classList.add('auth-msg--success');
          }
        } else if (msgEl) {
          msgEl.textContent = res.message || 'Request failed';
          msgEl.classList.add('auth-msg--error');
          if ((res.status || 0) === 429) {
            startForgotCooldown(FORGOT_COOLDOWN_SECONDS);
          }
        }
      });
    });
  }

  /* ----- Reset ----- */
  var resetForm = q('#resetForm');
  if (resetForm) {
    bindPasswordToggle('toggleResetPassword', 'password');
    bindPasswordToggle('toggleResetPassword2', 'password_confirmation');

    var rm = q('#resetStrengthMeter');
    var rb = q('#resetStrengthBar');
    var rl = q('#resetStrengthLabel');
    var rp = q('#password');
    if (rp) {
      rp.addEventListener('input', function () {
        updateStrengthMeter(rm, rb, rl, rp.value);
      });
    }

    resetForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = q('#resetBtn');
      var msgEl = q('#formMsg');
      if (msgEl) {
        msgEl.textContent = '';
        msgEl.className = 'auth-msg';
      }
      var fd = new FormData(resetForm);
      var data = {};
      fd.forEach(function (v, k) {
        data[k] = v;
      });
      postForm('reset-password', data, btn).then(function (res) {
        if (msgEl) {
          msgEl.textContent = res.message || '';
          msgEl.classList.add(res.success ? 'auth-msg--success' : 'auth-msg--error');
        }
        if (res.success && res.data && res.data.redirect) {
          setTimeout(function () {
            window.location.href = res.data.redirect;
          }, 600);
        }
        if (!res.success && res.errors) applyRegisterErrors(res.errors);
      });
    });
  }

  /* ----- Verify + resend ----- */
  var token = typeof window.__VERIFY_TOKEN__ === 'string' ? window.__VERIFY_TOKEN__.trim() : '';

  function showVerify(id, show) {
    var el = q('#' + id);
    if (el) el.classList.toggle('hidden', !show);
  }

  if (q('#verifyPending') || q('#verifyOk')) {
    if (token) {
      showVerify('verifyMissing', false);
      showVerify('verifyPending', true);
      fetch(routeQueryUrl('verify', { token: token }), {
        headers: { Accept: 'application/json' },
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (res) {
          showVerify('verifyPending', false);
          if (res.success) {
            showVerify('verifyOk', true);
            setTimeout(function () {
              window.location.href =
                (res.data && res.data.redirect) || routeQueryUrl('login');
            }, 1600);
          } else {
            showVerify('verifyFail', true);
          }
        })
        .catch(function () {
          showVerify('verifyPending', false);
          showVerify('verifyFail', true);
        });
    } else {
      showVerify('verifyPending', false);
      showVerify('verifyMissing', true);
    }
  }

  var resendForm = q('#resendForm');
  if (resendForm) {
    resendForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = q('#resendBtn');
      var msgEl = q('#resendMsg');
      if (msgEl) {
        msgEl.textContent = '';
        msgEl.className = 'auth-msg';
      }
      var fd = new FormData(resendForm);
      var data = {};
      fd.forEach(function (v, k) {
        data[k] = v;
      });
      postForm('resend-verification', data, btn).then(function (res) {
        if (msgEl) {
          msgEl.textContent = res.message || '';
          msgEl.classList.add(res.success ? 'auth-msg--success' : 'auth-msg--error');
        }
      });
    });
  }

})();
