/**
 * @file
 * Argue card library.
 */
(function ($, Drupal, debounce) {

  /**
   * Attaches card behavior for extendable content.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for card extendable content.
   */
  Drupal.behaviors.cardContentExtend = {

    attach: function (context) {
      let self = this;
      /**
       * Make details of argument teaser expandable.
       */
      let $cardExtendableCont = $(context).find('.card__content-item__extend');
      let eventData = { $elements: $cardExtendableCont };

      self.resetDataHeight(eventData, false);

      $cardExtendableCont.each(function (i, cec) {
        let $cec = $(cec);

        let $btn = $cec.next('.card__content-item__extend__toggle')
          ||  $cec.prev('.card__content-item__extend__toggle');
        $btn.addClass('listen-extend');

        $cec.addClass('content-dense');
        self.setHeight($cec);

        $btn.on('click', function () {
          $btn.toggleClass('show-extend');
          $cec.toggleClass('content-dense');
          self.setHeight($cec, true);
        });
      });

      // Listen on window resize and reset sizes of containers.
      $(window).on('resize', debounce(function() {
        self.resetDataHeight(eventData, true);
      }, 100));
    },

    /**
     * Set height on DOM element.
     *
     * @param {jQuery} $element
     *   The off-canvas dialog element.
     * @param {boolean} animate
     *   Animate extending the box.
     */
    setHeight: function($element, animate) {
      let self = this;
      if ($element.is('.content-dense')) {
        if (animate) {
          /*
           * First set height (from 'auto') to measured height, then set to
           * dense-height, to get animated slide effect.
           */
          let height = $element.height() + 'px';
          $element.css('height', height);
          setTimeout(function () {
            $element.css('height', $element.data('dense-height'));
          }, 100);
        } else {
          $element.css('height', $element.data('dense-height'));
        }
      } else {
        $element.css('height', $element.data('full-height'));
        let interval = 0;
        let timeOutHeight = $element.height();
        let timeOutFunc = setInterval(
          function() {
            /* Check while animation if container reached full height,
             * then change to height auto.
             */
            if ($element.height() === timeOutHeight) {
              $element.css('height', 'auto');
              clearInterval(timeOutFunc);
            } else {
              timeOutHeight = $element.height();
            }
            if (interval++ > 10) {
              // Something went wrong, just stop interval.
              clearInterval(timeOutFunc);
            }
          }, 300);
      }
    },

    /**
     * Adjusts the dialog on resize.
     *
     * @param {object} data
     *   Data attached to the event.
     * @param {boolean} setHeight
     *   Process the resize of .
     */
    resetDataHeight: function(data, setHeight) {
      let self = this;
      let $elements = data.$elements;
      $elements.each(function(i, element) {
        let $elm = $(element);
        $elm.data('dense-height', $elm.data('dense-height') || '10em')
        let heightAuto = $elm.css('height', 'auto').height();
        $elm.data('full-height', heightAuto);

        if (setHeight) {
          self.setHeight($elm, false);
        }
      });
    }

  };

})(jQuery, Drupal, Drupal.debounce);
