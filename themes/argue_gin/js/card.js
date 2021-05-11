
(function ($, Drupal) {


  Drupal.behaviors.cardContentExtend = {

    attach: function (context) {
      /**
       * Make details of argument teaser expandable.
       */
      let $cardExtendableCont = $(context).find('.card__content-item__extend');
      $cardExtendableCont.each(function (i, cec) {
        let $cec = $(cec);
        let fullHeight = $cec.height();
        $cec.data('full-height', fullHeight);
        let limitedHeight = $cec.data('height');

        let $btn = $cec.next('.card__content-item__extend__toggle')
          ||  $cec.prev('.card__content-item__extend__toggle');

        $btn.addClass('listen-extend');
        $cec.css('height', limitedHeight).addClass('content-dense');

        $btn.on('click', function () {
          $btn.toggleClass('show-extend');
          if ($btn.is('.show-extend')) {
            $cec.css('height', fullHeight).removeClass('content-dense');
          } else {
            $cec.css('height', limitedHeight).addClass('content-dense');
          }
        });
      });
    }

  };
})(jQuery, Drupal);
