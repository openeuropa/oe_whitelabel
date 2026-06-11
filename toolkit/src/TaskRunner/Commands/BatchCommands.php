<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_commands\TaskRunner\Commands;

use EcEuropa\Toolkit\TaskRunner\AbstractCommands;

/**
 * Defines ec-europa/toolkit commands to run tests.
 */
class BatchCommands extends AbstractCommands {

  /**
   * Runs PHPUnit tests in parallel.
   *
   * The tests are grouped like this:
   *   - One batch for each group name in toolkit.test.phpunit.batch_groups
   *     defined in runner.yml.dist, containing tests with that group.
   *   - One batch for remaining tests under ./tests/.
   *   - One batch for remaining tests under ./modules/.
   *
   * This system allows an efficient grouping, while only a few test classes
   * need an explicit `@group` annotation.
   *
   * @command toolkit:test-phpunit-batches
   */
  public function toolkitTestPhpunitBatches() {
    $phpunit_bin = $this->getBin('phpunit');
    $working_directory = $this->getWorkingDir();

    $batch_groups = $this->getConfig()->get('toolkit.test.phpunit.batch_groups');

    // Collect commands before adding them to the parallel collection.
    // This is useful for debugging and reporting.
    $commands = [];
    // Add batch groups.
    foreach ($batch_groups as $group_name) {
      $commands[] = "# Group: $group_name
$phpunit_bin --fail-on-empty-test-suite --log-junit='$working_directory/junit-export/phpunit-$group_name.xml' --group='$group_name'";
    }
    // Add batches for remaining tests split by tests/ and modules/.
    foreach (['tests', 'modules'] as $path) {
      $command = "# Path: $path
$phpunit_bin --fail-on-empty-test-suite --log-junit='$working_directory/junit-export/phpunit-$path.xml' $path";
      if ($batch_groups) {
        $command .= " --exclude-group='" . implode(',', $batch_groups) . "'";
      }
      $commands[] = $command;
    }

    // Run tests in parallel batches.
    $parallel = $this->taskParallelExec()->printOutput();
    foreach ($commands as $cmd) {
      $parallel->process($cmd);
    }

    $collection = $this->collectionBuilder();
    $collection->addTask($parallel);
    return $collection;
  }

}
