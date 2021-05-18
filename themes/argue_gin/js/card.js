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

      let textareaObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.attributeName === "style") {
            let regEx = /height:[^;]*;/;

            let old = (mutation.oldValue)
              ? mutation.oldValue.match(regEx) : null;
            old = (old && old.length) ? old.shift() : null;

            let cur = mutation.target.getAttribute("style");
            cur = (cur) ? cur.match(regEx) : null;
            cur = (cur && cur.length) ? cur.shift() : '';
            if (old && old !== cur) {
              $(window).trigger('resize');
            }
          }
        });
      });

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
          self.setHeight($cec);
        });

        $cec.find('textarea').each(function(i, textarea) {
          textareaObserver.observe(textarea, {
            attributes: true,
            attributeOldValue: true,
            attributeFilter: ['style'] });
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
     */
    setHeight: function($element) {
      if ($element.is('.content-dense')) {
        $element.css('height', $element.data('dense-height'));
      } else {
        $element.css('height', $element.data('full-height'));
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
          self.setHeight($elm);
        }
      });
    }

  };

})(jQuery, Drupal, Drupal.debounce);
