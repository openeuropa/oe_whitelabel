/**
 * @file
 * Attaches behaviors for the project status element.
 */
(function (Drupal, once) {

  /**
   * List of background classes.
   *
   * @type {string[]}
   */
  const colorClasses = [
    'bg-secondary',
    'bg-info',
    'bg-dark',
  ];

  /**
   * Animates the project status badge and progress bar.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initialises the behavior.
   */
  Drupal.behaviors.projectStatus = {
    attach: function (context) {
      const statusComponents = once('oe-wt-project-status', '.bcl-project-status', context);
      const statusBadges = once('oe-wt-project-status', '.badge.oe-wt-project__status', context);

      statusComponents.forEach(function (wrapper) {
        const badges = wrapper.getElementsByClassName('badge');
        const progressBars = wrapper.getElementsByClassName('progress-bar');

        calculateProjectStatusAndProgress(wrapper, badges, progressBars);
      });

      statusBadges.forEach(function (wrapper) {
        // Status badges don't have the progress element.
        calculateProjectStatusAndProgress(wrapper, [wrapper], []);
      });
    }
  };

  /**
   * Calculates the values for the project status and progress elements.
   *
   * @param {Element} wrapper
   *   The element that holds the project data.
   * @param {array.<Element>} badges
   *   A list of badges to process.
   * @param {array.<Element>} progressBars
   *   A list of progress bars to process.
   */
  function calculateProjectStatusAndProgress(wrapper, badges, progressBars) {
    const msBegin = wrapper.dataset.startTimestamp * 1000;
    const msEnd = wrapper.dataset.endTimestamp * 1000;
    const statusLabels = wrapper.dataset.statusLabels.split('|');
    const msNow = Date.now();
    // Calculate a status id: planned = 0, ongoing = 1, closed = 2.
    const status = (msNow >= msBegin) + (msNow > msEnd);
    // Calculate a progress: planned = 0, ongoing = 0..1, closed = 1.
    const progress01 = Math.max(0, Math.min(1, (msNow - msBegin) / (msEnd - msBegin)));
    // Convert to percent: planned = 0%, ongoing = 0%..100%, closed = 100%.
    // Round to 1%, to avoid overwhelming float digits in aria attributes.
    const percent = Math.round(progress01 * 100);

    // Process the status label.
    Array.from(badges).forEach(function (badge) {
      badge.classList.remove(...colorClasses);
      badge.classList.add(colorClasses[status]);
      badge.innerHTML = statusLabels[status];
    });

    // Process the progress bar.
    Array.from(progressBars).forEach(function (progressBar) {
      progressBar.classList.remove(...colorClasses);
      progressBar.classList.add(colorClasses[status]);
      progressBar.style.width = percent + '%';
      progressBar.setAttribute('aria-valuenow', percent);
      progressBar.setAttribute('aria-label', percent);
    });

    // Reveal the wrapper element.
    wrapper.classList.remove('d-none');
  }

})(Drupal, once);
