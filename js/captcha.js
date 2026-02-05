let tout;

function redo_events(provider, extra) {
  const elements = document.querySelectorAll('.captcha .captcha_text, textarea[id="body"]');
  elements.forEach(el => {
    el.removeEventListener('focus', actually_load_captcha);
    el.addEventListener('focus', () => { actually_load_captcha(provider, extra); }, { once: true });
  });
}

function actually_load_captcha(provider, extra) {
  const elements = document.querySelectorAll('.captcha .captcha_text, textarea[id="body"]');
  elements.forEach(el => {
    el.removeEventListener('focus', actually_load_captcha);
  });

  if (tout !== undefined) {
    clearTimeout(tout);
  }

  fetch(provider + '?mode=get&extra=' + encodeURIComponent(extra))
    .then(response => response.json())
    .then(json => {
      document.querySelector(".captcha .captcha_cookie").value = json.cookie;
      document.querySelector(".captcha .captcha_html").innerHTML = json.captchahtml;

      setTimeout(() => {
        redo_events(provider, extra);      
      }, json.expires_in * 1000);
    })
    .catch(err => console.error('Error loading captcha:', err));
}

function load_captcha(provider, extra) {
  onReady(() => {
    document.querySelector(".captcha>td").innerHTML = "<input class='captcha_text' type='text' name='captcha_text' size='32' maxlength='6' autocomplete='off'>"+
			  "<input class='captcha_cookie' name='captcha_cookie' type='hidden'>"+
			  "<div class='captcha_html'><img src='/static/clickme.gif'></div>";

    const qrCaptcha = document.querySelector("#quick-reply .captcha .captcha_text");
    if (qrCaptcha) {
      qrCaptcha.placeholder = _("Verification");
    }

    document.querySelector(".captcha .captcha_html").addEventListener("click", () => { actually_load_captcha(provider, extra); });
    document.addEventListener("ajax_after_post", () => { actually_load_captcha(provider, extra); });
    redo_events(provider, extra);

    window.addEventListener("quick-reply", () => {
      redo_events(provider, extra);
      const qrHtml = document.querySelector("#quick-reply .captcha .captcha_html");
      const formHtml = document.querySelector("form:not(#quick-reply) .captcha .captcha_html");
      if (qrHtml && formHtml) {
        qrHtml.innerHTML = formHtml.innerHTML;
      }
      const qrCookie = document.querySelector("#quick-reply .captcha .captcha_cookie");
      const formCookie = document.querySelector("form:not(#quick-reply) .captcha .captcha_cookie");
      if (qrCookie && formCookie) {
        qrCookie.value = formCookie.value;
      }
      document.querySelector("#quick-reply .captcha .captcha_html").addEventListener("click", () => { actually_load_captcha(provider, extra); });
    });
  });
}
