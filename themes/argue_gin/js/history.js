/**
 * @file
 * Argue history library.
 */
(function(Drupal, $, drupalSettings) {

  /**
   * Attaches history behavior for mark content as new.
   *
   * @type {Drupal~behaviors}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for card extendable content.
   */
  Drupal.behaviors.historyIsNew = {
    attach: function(context) {
      const currentUserID = parseInt(drupalSettings.user.uid, 10);

      if (Drupal.history && currentUserID) {
        let $nodes = $(context).find('[data-history-node-id]');
        let nodeList = [];
        $nodes.each(function(i, node) {
          let $node = $(node);
          let nid = $node.attr('data-history-node-id');
          let $marker = $node.find("#node_mark_".concat(nid));
          if ($marker.length) {
            let timestamp = $marker.attr('data-history-changed') || false;
            if (timestamp) {
              if (Drupal.history.needsServerCheck(nid, timestamp)) {
                nodeList.push(nid);
              }
            }
          }
        });

        if (nodeList.length) {
          Drupal.history.fetchTimestamps(nodeList, function () {
            for (let nid of nodeList) {
              let $marker = $(context).find("#node_mark_".concat(nid));
              if ($marker.length) {
                const lastViewTimestamp = Drupal.history.getLastRead(nid);
                let timestamp = $marker.attr('data-history-changed') || 0;
                timestamp = parseInt(timestamp, 10);
                if (timestamp && (timestamp > lastViewTimestamp)) {
                  $marker.removeClass('hidden').text(Drupal.t('new'));
                }
              }
            }
          });
        }
      }
    }
  }

})(Drupal, jQuery, drupalSettings);
