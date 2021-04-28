
(function ($, Drupal) {


  Drupal.behaviors.argumentTeaser = {

    attach: function attach(context) {
      /**
       * Make details of argument teaser expandable.
       */
      let $jsElements = $(context).find('.argument-teaser [data-js-class]');
      $jsElements.each(function (i, jse) {
        $(jse).addClass($(jse).attr('data-js-class'));

        if ($(jse).is('[data-slide-toggle-label]')) {
          let label = $(jse).attr('data-slide-toggle-label');
          $('<button class="argument-teaser__toggle button button--extrasmall" role="switch">'
            + '<span class="material-icons">zoom_in</span>'+ label +'</button>')
            .on('click', function() {
              $(jse).slideToggle(300);
              $(this).toggleClass('open')
            }).insertBefore($(jse));
        }

      });
    }

  };
})(jQuery, Drupal);
