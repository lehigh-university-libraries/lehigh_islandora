(function ($, Drupal) {
  Drupal.behaviors.lehighIslandoraNodeForm = {
    attach: function (context, settings) {
      function isValidString(inputString) {
        return /^[A-Za-z0-9-_"' ?]+$/.test(inputString);
      }
      $(once('add-search', '.browse', context)).first().each(function () {
        var s = Drupal.behaviors.lehighNode.getQueryParam('search_api_fulltext');
        if (s != null && s != "" && isValidString(s)) {
          $('.browse .node--type-islandora-object a').each(function() {
            let h = $(this).attr('href') + '?search_api_fulltext=' + s;
            $(this).attr('href', h)
          })
        }
      });
      $(once('populate-title', 'textarea[name="field_full_title[0][value]"]', context)).each(function () {
          const textarea = document.querySelector('textarea[name="field_full_title[0][value]"]');
          const input = document.querySelector('input[name="title[0][value]"]');          
          textarea.addEventListener('input', function() {
            let textareaValue = textarea.value;
            if (textareaValue.length > 255) {
              textareaValue = textareaValue.substring(0, 255);
            }            
            input.value = textareaValue;
          });
      });
    }
  };
})(jQuery, Drupal);
