
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
          $('<button class="argument-teaser__toggle details--handle button button--extrasmall" role="switch">'
            + '<span class="material-icons more">unfold_more</span>'
            + '<span class="material-icons less">unfold_less</span>'
            + '<span class="details--text">' + label +'</span></button>')
            .on('click', function() {
              $(jse).slideToggle(300);
              $(this).toggleClass('open')
            }).insertAfter($(jse));
        }

      });
    }

  };
})(jQuery, Drupal);
