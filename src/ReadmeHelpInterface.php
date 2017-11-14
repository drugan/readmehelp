<?php

namespace Drupal\readmehelp;

/**
 * Defines the interface for readmehelp markdown filter.
 */
interface ReadmeHelpInterface {

  /**
   * The versions of a README file by default converted by the filter.
   */
  const READMEHELP_FILES = 'README.md, README.txt, README';

}
