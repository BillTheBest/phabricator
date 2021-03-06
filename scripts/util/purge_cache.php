#!/usr/bin/env php
<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$root = dirname(dirname(dirname(__FILE__)));
require_once $root.'/scripts/__init_script__.php';

phutil_require_module('phutil', 'console');
phutil_require_module('phabricator', 'storage/queryfx');

$purge_changesets   = false;
$purge_differential = false;
$purge_maniphest    = false;

$args = array_slice($argv, 1);
if (!$args) {
  usage("Specify which caches you want to purge.");
}

foreach ($args as $arg) {
  switch ($arg) {
    case '--all':
      $purge_changesets = true;
      $purge_differential = true;
      $purge_maniphest = true;
      break;
    case '--changesets':
      $purge_changesets = true;
      break;
    case '--differential':
      $purge_differential = true;
      break;
    case '--maniphest':
      $purge_maniphest = true;
      break;
    case '--help':
      return help();
    default:
      return usage("Unrecognized argument '{$arg}'.");
  }
}

if ($purge_changesets) {
  echo "Purging changeset cache...\n";
  $table = new DifferentialChangeset();
  queryfx(
    $table->establishConnection('w'),
    'TRUNCATE TABLE %T',
    DifferentialChangeset::TABLE_CACHE);
  echo "Done.\n";
}

if ($purge_differential) {
  echo "Purging Differential comment cache...\n";
  $table = new DifferentialComment();
  queryfx(
    $table->establishConnection('w'),
    'UPDATE %T SET cache = NULL',
    $table->getTableName());
  echo "Done.\n";
}

if ($purge_maniphest) {
  echo "Purging Maniphest comment cache...\n";
  $table = new ManiphestTransaction();
  queryfx(
    $table->establishConnection('w'),
    'UPDATE %T SET cache = NULL',
    $table->getTableName());
  echo "Done.\n";
}

echo "Ok, caches purged.\n";

function usage($message) {
  echo "Usage Error: {$message}";
  echo "\n\n";
  echo "Run 'purge_cache.php --help' for detailed help.\n";
  exit(1);
}

function help() {
  $help = <<<EOHELP
**SUMMARY**

    **purge_cache.php** [--maniphest] [--differential] [--changesets]
    **purge_cache.php** --all
    **purge_cache.php** --help

    Purge various long-lived caches. Normally, Phabricator caches some data for
    a long time or indefinitely, but certain configuration changes might
    invalidate these caches. You can use this script to manually purge them.

    For instance, if you change display widths in Differential or configure
    syntax highlighting, you may want to purge the changeset cache (with
    "--changesets") so your changes are reflected in older diffs.

    If you change Remarkup rules, you may want to purge the Maniphest or
    Differential comment caches ("--maniphest", "--differential") so older
    comments pick up the new rules.

    __--all__
        Purge all long-lived caches.

    __--changesets__
        Purge Differential changeset render cache.

    __--differential__
        Purge Differential comment formatting cache.

    __--maniphest__: show this help
        Purge Maniphest comment formatting cache.

    __--help__: show this help


EOHELP;
  echo phutil_console_format($help);
  exit(1);
}
