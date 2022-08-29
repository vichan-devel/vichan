var tout;

function redo_events(provider, extra) {
  $('.captcha .captcha_text, textarea[id="body"]').off("focus").one("focus", function() { actually_load_captcha(provider, extra); });
}

function actually_load_captcha(provider, extra) {
  $('.captcha .captcha_text, textarea[id="body"]').off("focus");

  if (tout !== undefined) {
    clearTimeout(tout);
  }

  $.getJSON(provider, {mode: 'get', extra: extra}, function(json) {
    $(".captcha .captcha_cookie").val(json.cookie);
    $(".captcha .captcha_html").html(json.captchahtml);

    setTimeout(function() {
      redo_events(provider, extra);      
    }, json.expires_in * 1000);
  });
}

function load_captcha(provider, extra) {
  $(function() {
    $(".captcha>td").html("<input class='captcha_text' type='text' name='captcha_text' size='32' maxlength='6' autocomplete='off'>"+
			  "<input class='captcha_cookie' name='captcha_cookie' type='hidden'>"+
			  "<div class='captcha_html'><img src='/static/clickme.gif'></div>");

    $("#quick-reply .captcha .captcha_text").prop("placeholder", _("Verification"));

    $(".captcha .captcha_html").on("click", function() { actually_load_captcha(provider, extra); });
    $(document).on("ajax_after_post",       function() { actually_load_captcha(provider, extra); });
    redo_events(provider, extra);

    $(window).on("quick-reply", function() {
      redo_events(provider, extra);
      $("#quick-reply .captcha .captcha_html").html($("form:not(#quick-reply) .captcha .captcha_html").html());
      $("#quick-reply .captcha .captcha_cookie").val($("form:not(#quick-reply) .captcha .captcha_cookie").html());
      $("#quick-reply .captcha .captcha_html").on("click", function() { actually_load_captcha(provider, extra); });
    });
  });
}
