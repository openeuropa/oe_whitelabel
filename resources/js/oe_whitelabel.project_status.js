/**
 * @file
 * Attaches behaviors for the project status element.
 */
(function (bootstrap, Drupal, $) {

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
      $('.bcl-project-status', context).once('bcl-project-status').each(function () {
        const $element = $(this);
        const msBegin = $element.data('start-timestamp') * 1000;
        const msEnd = $element.data('end-timestamp') * 1000;
        const statusLabels = $element.data('status-labels').split('|');
        const msNow = Date.now();
        const status = (msNow >= msBegin) + (msNow > msEnd);
        const progress01 = Math.max(0, Math.min(1, (msNow - msBegin) / (msEnd - msBegin)));
        const percent = Math.round(progress01 * 200) / 2;

        // Process the status label.
        $('.badge', this).each(function () {
          const $element = $(this);
          $element.removeClass(colorClasses);
          $element.addClass(colorClasses[status]);
          if (statusLabels) {
            $element.html(statusLabels[status]);
          }
        });

        // Process the progress bar.
        $('.progress-bar', this).each(function () {
          const $element = $(this);
          $element.removeClass(colorClasses);
          $element.addClass(colorClasses[status]);
          $element.css('width', percent + '%');
          $element.attr('aria-valuenow', percent);
          $element.attr('aria-label', percent);
        });

        // Reveal the entire section.
        $element.removeClass('d-none');
      });
    }
  };

})(bootstrap, Drupal, jQuery);
