/**
 * @file
 * Attaches behaviors for the Tabs paragraph.
 */
(function (Drupal, once) {

  /**
   * Opens the closed tabs around the URL target, then scrolls to it.
   *
   * @param {string} hash
   *   The URL hash.
   */
  function openTarget(hash) {
    const id = decodeURIComponent((hash || '').replace(/^#/, ''));
    const element = id ? document.getElementById(id) : null;
    if (!element) {
      return;
    }

    let opened = false;
    let pane = element.closest('.tab-pane');
    while (pane) {
      if (!pane.classList.contains('active')) {
        const trigger = document.querySelector('[data-bs-toggle="tab"][data-bs-target="#' + CSS.escape(pane.id) + '"]');
        if (trigger) {
          bootstrap.Tab.getOrCreateInstance(trigger).show();
          opened = true;
        }
      }
      pane = pane.parentElement ? pane.parentElement.closest('.tab-pane') : null;
    }

    // A tab pane itself: scroll to its tab list.
    const scrollTo = element.classList.contains('tab-pane') ? element.parentElement.previousElementSibling : element;
    if (opened && scrollTo) {
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

})(Drupal, once);
