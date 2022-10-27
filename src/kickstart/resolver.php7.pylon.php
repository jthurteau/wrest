<?php

/**
 * Multiviews gateway resovlver pylon, 
 * set resolver hint and forward to main gateway pylon
 *
 * PHP version 7
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/resolver.php7.pylon.php
 * @link   install:public/$1.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

define('Saf\RESOLVER_PYLON', basename(__FILE__, '.php'));
return require_once('index.php');