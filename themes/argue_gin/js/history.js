(function(Drupal, $, drupalSettings, storage) {

  /**
   * Attaches card behavior for extendable content.
   *
   * @type {Drupal~behaviors}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for card extendable content.
   */
  Drupal.behaviors.historyIsNew = {
    attach: function(context) {
      if (drupalSettings.history && drupalSettings.history.nodesToMarkAsRead) {
        let nodesMarkAsRead = drupalSettings.history.nodesToMarkAsRead;
        let currentUserID = parseInt(drupalSettings.user.uid, 10);

        let someMinutes = 2 * 60;
        let now = Math.round(new Date().getTime() / 1000);
        let someMinutesAgo = now - someMinutes;

        console.log('nodesToMarkAsRead', nodesMarkAsRead);

        let $nodes = $(context).find('[data-history-node-id]');
        $nodes.each(function(i, node) {
          let $node = $(node);
          let nid = $node.attr('data-history-node-id');
          let $marker = $node.find("#node_mark_".concat(nid));

          if ($marker.length) {
            let isNew = false;
            // Enable label when NOT mark as read
            if (typeof nodesMarkAsRead[nid] === 'undefined') {
              isNew = true;
              console.log('NOT marked as read');
            } else {
              // Enable label when mark as read since some minutes ago.
              let histId = "Drupal.history.".concat(currentUserID, ".").concat(nid);
              let marTime = storage.getItem(histId) || false;
              if (marTime && marTime > someMinutesAgo) {
                isNew = true;
                console.log('MAR some minutes ago', marTime, someMinutesAgo, marTime - someMinutesAgo);
              } else {
                console.log('MAR more then some minutes ago', marTime, someMinutesAgo, marTime - someMinutesAgo );
              }
            }

            if (isNew) {
              $marker.removeClass('hidden').text(Drupal.t('new'))
            }
          }

        });


      }

      /**
      if (typeof Drupal.history !== 'undefined') {
        let $articles = $(context).find('.node[data-history-node-id]');
        let nIds = [];
        let nodeTimes = {};
        $articles.each(function(i, article) {
          let $article = $(article);
          let $marker = $article.find('mark[data-history-nid]');
          let nid = $marker.attr('data-history-nid');
          nodeTimes[nid] = {
            created: $marker.attr('data-history-created'),
            updated: $marker.attr('data-history-updated')
          }
          nIds.push(parseInt($article.attr('data-history-node-id'), 10));
        })

        console.log(nIds, nodeTimes);

        Drupal.history.fetchTimestamps(nIds, function() {
          for (let nodeID of nIds) {
            let str = "Drupal.history.".concat(currentUserID, ".").concat(nodeID);
            console.log(str);
            console.log(storage.getItem(str));
          }
        });
      }
       */
    }
  }

})(Drupal, jQuery, drupalSettings, window.localStorage);
