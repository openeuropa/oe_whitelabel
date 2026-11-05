/**
 * @file
 * Attaches behaviors for the Tabs paragraph.
 */
(function (bootstrap, Drupal, once) {

  /**
   * Opens the closed tab around the URL target, then scrolls to it.
   *
   * @param {string} hash
   *   The URL hash.
   */
  function openTarget(hash) {
    const id = decodeURIComponent((hash || '').replace(/^#/, ''));
    const element = id ? document.getElementById(id) : null;
    const pane = element ? element.closest('.bcl-tabs .tab-pane') : null;
    if (!pane || pane.classList.contains('active')) {
      return;
    }

    const trigger = document.querySelector('.bcl-tabs [data-bs-toggle="tab"][data-bs-target="#' + CSS.escape(pane.id) + '"]');
    if (!trigger) {
      return;
    }
    bootstrap.Tab.getOrCreateInstance(trigger).show();

    // A tab pane itself: scroll to its tab list.
    const scrollTo = element === pane ? pane.parentElement.previousElementSibling : element;
    if (scrollTo) {
      scrollTo.scrollIntoView();
    }
  }

  /**
   * One URL per tab, and the tab of a targeted anchor is opened.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initialises the behavior.
   */
  Drupal.behaviors.oeWhitelabelTabs = {
    attach: function (context) {
      once('oe-wt-tabs', 'html', context).forEach(function () {
        openTarget(window.location.hash);
        window.addEventListener('hashchange', function () {
          openTarget(window.location.hash);
        });
      });

      // Keep the URL in line with the open tab.
      once('oe-wt-tabs-url', '.bcl-tabs [data-bs-toggle="tab"]', context).forEach(function (trigger) {
        trigger.addEventListener('shown.bs.tab', function () {
          const target = trigger.getAttribute('data-bs-target');
          const pane = document.querySelector(target);
          const id = decodeURIComponent(window.location.hash.replace(/^#/, ''));
          const current = id ? document.getElementById(id) : null;
          // Keep a URL that already points inside this tab.
          if (pane && current && pane.contains(current)) {
            return;
          }
          history.replaceState(null, '', target);
        });
      });
    }
  };

})(bootstrap, Drupal, once);
