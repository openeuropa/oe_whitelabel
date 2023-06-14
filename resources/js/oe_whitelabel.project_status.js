/**
 * @file
 * Attaches behaviors for the project status element.
 */
(function (bootstrap, Drupal, once) {

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
      const bclProjectStatus = once('bcl-project-status', '.bcl-project-status', context);

      bclProjectStatus.forEach(function (element) {
        var msBegin = element.dataset.startTimestamp * 1000;
        var msEnd = element.dataset.endTimestamp * 1000;
        var statusLabels = element.dataset.statusLabels.split('|');
        var msNow = Date.now();
        // Calculate a status id: planned = 0, ongoing = 1, closed = 2.
        var status = (msNow >= msBegin) + (msNow > msEnd);
        // Calculate a progress: planned = 0, ongoing = 0..1, closed = 1.
        var progress01 = Math.max(0, Math.min(1, (msNow - msBegin) / (msEnd - msBegin)));
        // Convert to percent: planned = 0%, ongoing = 0%..100%, closed = 100%.
        // Round to 1%, to avoid overwhelming float digits in aria attributes.
        var percent = Math.round(progress01 * 100);

        // Process the status label.
        var badges = element.getElementsByClassName('badge');
        Array.from(badges).forEach(function(badge) {
          badge.classList.remove(...colorClasses);
          badge.classList.add(colorClasses[status]);
          badge.innerHTML = statusLabels[status];
        });

        // Process the progress bar.
        var progressBars = element.getElementsByClassName('progress-bar');
        Array.from(progressBars).forEach(function(progressBar) {
          progressBar.classList.remove(...colorClasses);
          progressBar.classList.add(colorClasses[status]);
          progressBar.style.width = percent + '%';
          progressBar.setAttribute('aria-valuenow', percent);
          progressBar.setAttribute('aria-label', percent);
        });

        // Reveal the entire section.
        element.classList.remove('d-none');
      });
    }
  };

})(bootstrap, Drupal, once);
