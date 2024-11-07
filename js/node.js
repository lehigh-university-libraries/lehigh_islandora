(function ($, Drupal) {
  Drupal.behaviors.lehighIslandoraNode = {
    attach: function (context, settings) {
      $(once('access-request', 'a[data-bs-target="#accessRequestModal"]', context)).one('click', function (e) {
        $.ajax({
            url: '/ajax/access-request-form',
            type: 'GET',
            success: function (response) {
              var script = document.createElement('script');
              script.type = 'text/javascript';
              script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
              script.onload = function() {
                $('#access-request-form-container').html(response.form);
                Drupal.attachBehaviors();
              }
              document.head.appendChild(script);
            },
            error: function () {
                console.error('Failed to load access request form.');
            }
        });
      });
    }
  }
})(jQuery, Drupal)
