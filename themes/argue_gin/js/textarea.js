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
  Drupal.behaviors.textarea = {
    attach: function (context) {
      autosize($('textarea.form-element--type-textarea', context));
    }
  }
})(jQuery, Drupal);
