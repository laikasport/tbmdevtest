(function($) {
  $(document).ready(function () {
    var options = $('#mepr_account_nav_tabs_items'),
      checkbox = $('#mepr_account_nav_tabs_enabled');
    if(checkbox.is(":checked")) {
      options.show();
    } else {
      options.hide();
    }
    checkbox.on('click', function() {
      options.slideToggle('fast');
    });

    $('#mepr_account_nav_tabs_items').on('click', '#mepr-add-tab', function (e) {
      e.preventDefault();
      show_nav_tab_form();
    });

    function show_nav_tab_form() {
      var data = {
        action: 'add_new_tab_form',
      };
      $.post(ajaxurl, data, function(response) {
        if( response.error === undefined ) {
          $(response).hide().appendTo('#mepr_account_nav_tabs_list').slideDown('fast');
        } else {
          alert('Error');
        }
      });
    }

    $('#mepr_account_nav_tabs_items').on('click', '.mepr_nav_tab_item_delete', function (e) {
      e.preventDefault();
      var id = $(this).parent().data('id');
      if (confirm(MeprAccountNavTabs.confirmDelete)) {
        $('#mepr_nav_tab_item_' + id).fadeOut('fast', function () {
          $(this).remove();
        })
      }
    });

    $('#mepr_account_nav_tabs_items').on('click', '.mepr_account_nav_tabs_tab_radio', function (e) {
      var id = $(this).data('id');
      var type = $(this).data('type');

      if(type == 'content') {
        $('#mepr_nav_tab_url_' + id).hide();
        $('#mepr_nav_tab_content_' + id).slideDown();
      } else {
        $('#mepr_nav_tab_content_' + id).hide();
        $('#mepr_nav_tab_url_' + id).slideDown();
      }
    });

  });
})(jQuery);
