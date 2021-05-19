/**
 * @file
 * Argue card library.
 */
(function ($, Drupal) {

  /**
   * Attaches card behavior for extendable content.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for card extendable content.
   */
  Drupal.behaviors.textarea = {
    attach: function (context) {

      // Attach autoresize behavior so all texts can be seen without scrolling.
      let $textarea = $('textarea.form-element--type-textarea', context);
      $textarea.each(function(){
        autosize(this);
      }).on('autosize:resized', function() {
        // Trigger a window resize for components depending on the box height.
        $(window).trigger('resize');
      });

    }
  }
})(jQuery, Drupal);
