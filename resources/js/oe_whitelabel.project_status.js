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
   * Passes an updated status value to a function at given timestamps.
   *
   * @param {int} msBegin
   *   Start timestamp in milliseconds.
   * @param {int} msEnd
   *   End timestamp in milliseconds.
   * @param {function(0|1|2)} setStatus
   *   Callback to set the status: 0 = planned, 1 = ongoing, 2 = closed.
   */
  function animateStatus(msBegin, msEnd, setStatus) {
    // @todo setTimeout() only works properly for durations up to ~10 years.
    const msNow = Date.now();
    if (msNow < msBegin) {
      setStatus(0);
      window.setTimeout(setStatus, msBegin - msNow, 1);
      window.setTimeout(setStatus, msEnd - msNow, 2);
    }
    else if (msNow < msEnd) {
      setStatus(1);
      window.setTimeout(setStatus, msEnd - msNow, 2);
    }
    else {
      setStatus(2);
    }
  }

  /**
   * Passes an updated progress value to a function at a series of timestamps.
   *
   * @param {int} msBegin
   *   Start timestamp in milliseconds.
   * @param {int} msEnd
   *   End timestamp in milliseconds.
   * @param {int} nTicks
   *   Number of sub-intervals.
   * @param {function(int)} setProgress
   *   Callback to be called on each tick.
   *   The parameter is a value within the [0..nTicks] interval.
   */
  function animateProgress(msBegin, msEnd, nTicks, setProgress) {
    const msNow = Date.now();
    const msTick = (msEnd - msBegin) / nTicks;
    const tickNext = Math.ceil((msNow - msBegin) / msTick);

    if (tickNext >= nTicks) {
      setProgress(nTicks);
      return;
    }

    // Compute a delay for the start of the interval.
    const msIntervalDelay = msBegin - msNow + Math.max(0, tickNext) * msTick;

    // Register a repeated interval.
    // @todo setTimeout() only works properly for durations up to ~10 years.
    window.setTimeout(function () {
      let tick = Math.max(0, tickNext);
      setProgress(tick);
      if (tick >= nTicks) {
        return;
      }
      ++tick;
      const intervalId = window.setInterval(function () {
        setProgress(tick);
        if (tick >= nTicks) {
          clearInterval(intervalId);
        }
        ++tick;
      }, msTick);
    }, msIntervalDelay);

    // Set initial progress.
    setProgress(Math.max(0, tickNext - 1));
  }

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

        // Process the status label.
        $('.badge', this).each(function () {
          const $element = $(this);
          animateStatus(msBegin, msEnd, function (status) {
            $element.removeClass(colorClasses);
            $element.addClass(colorClasses[status]);
            if (statusLabels) {
              $element.html(statusLabels[status]);
            }
          });
        });

        // Process the progress bar.
        $('.progress-bar', this).each(function () {
          const $element = $(this);
          animateStatus(msBegin, msEnd, function (status) {
            $element.removeClass(colorClasses);
            $element.addClass(colorClasses[status]);
          });
          // Disable css transition.
          // It looks bad for the initial setting, and afterwards it does not help.
          $element.css('transition', false);
          const factor = 2;
          animateProgress(msBegin, msEnd, factor * 100, function (tick) {
            const percent = tick / factor;
            $element.css('width', percent + '%');
            $element.attr('aria-valuenow', percent);
            $element.attr('aria-label', percent);
          });
        });

        // Reveal the entire section.
        $element.removeClass('d-none');
      });
    }
  };

})(bootstrap, Drupal, jQuery);
