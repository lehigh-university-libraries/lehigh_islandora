(function ($, Drupal) {
  Drupal.behaviors.lehighIslandoraNodeForm = {
    attach: function (context, settings) {
      function isValidString(inputString) {
        return /^[A-Za-z0-9-_"' ?]+$/.test(inputString);
      }

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
          $('label').addClass('form-item__label form-item__label--multiple-value-form');
          $('#edit-field-part-detail-1-type').val('volume');
          $('#edit-field-part-detail-2-type').val('issue');
          $('#edit-field-part-detail-3-type').val('page');
          $('#edit-field-part-detail-0-title').attr('placeholder', 'Publication Title');
          $('#edit-field-edtf-date-created-0-value').attr('placeholder', 'YYYY-MM-DD');
          $('#edit-field-input-source-0-value').val('input_form');
          const published = document.getElementById('edit-status-value');
          published.checked = false;
          const options = {
            '': '- Select',
            'http://rightsstatements.org/vocab/InC/1.0/': 'IN COPYRIGHT',
            'http://rightsstatements.org/vocab/InC-EDU/1.0/': 'IN COPYRIGHT - EDUCATIONAL USE PERMITTED',
            'http://rightsstatements.org/vocab/InC-NC/1.0/': 'IN COPYRIGHT - NON-COMMERCIAL USE PERMITTED',
            'http://rightsstatements.org/vocab/CNE/1.0/': 'I don’t know, get in touch with more information'
          };

          // Find the input element by its ID
          const rights = document.getElementById('edit-field-rights-0-value');

          // Create a new select element
          const select = document.createElement('select');
          select.id = rights.id;
          select.name = rights.name;
          select.className = rights.className;
          select.setAttribute('data-drupal-selector', rights.getAttribute('data-drupal-selector'));

          // Populate the select element with the options
          for (const [value, text] of Object.entries(options)) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            select.appendChild(option);
          }

          // Replace the input element with the new select element
          rights.parentNode.replaceChild(select, rights);
        });
    }
  };
})(jQuery, Drupal);
(function ($, Drupal) {
  Drupal.behaviors.autoTaxonomyWorkflow = {
    attach: function (context, settings) {
      const existingButtonSelector = 'input[data-drupal-selector="edit-field-self-submission-creator-actions-ief-add-existing"]';
      const newButtonSelector = 'input[data-drupal-selector="edit-field-self-submission-creator-actions-ief-add"]';
      const cancelButtonSelector = 'input[name="ief-reference-cancel-field_self_submission_creator-form"]';
      const searchResultsSelector = '.view-entity-browser-creators'; // Update as per actual search result container selector

      $(newButtonSelector).attr("value", "Add a new person");
      $(existingButtonSelector).attr("value", "Find an existing person in The Preserve");
      return;

      function clickAddExistingTerm() {
        // Click "Add Existing Taxonomy Term" button to show search form
        const existingButton = $(existingButtonSelector);
        if (existingButton.length) {
          existingButton[0].dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
        }
      }

      function checkForResults() {
        // Check if search results are empty, wait if results haven't loaded yet
        const searchResults = $(searchResultsSelector);
        if (searchResults.length && searchResults.children().length === 0) {
          clickCancel();
        } else {
          // Wait and check again if results aren't ready
          setTimeout(checkForResults, 500);
        }
      }

      function clickCancel() {
        // Click the cancel button on the search form
        const cancelButton = $(cancelButtonSelector);
        if (cancelButton.length) {
          cancelButton[0].dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
          setTimeout(clickAddNewTerm, 500); // Wait for UI to reset and re-enable buttons
        }
      }

      function clickAddNewTerm() {
        // Click "Add New Taxonomy Term" button after reset
        const newButton = $(newButtonSelector);
        if (newButton.length) {
          newButton[0].dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
        }
      }

      // Start the workflow by clicking "Add Existing Taxonomy Term"
        clickAddExistingTerm();
        setTimeout(checkForResults, 10000);
    }
  };
})(jQuery, Drupal);
