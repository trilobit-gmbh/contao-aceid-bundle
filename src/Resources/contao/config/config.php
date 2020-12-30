<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

use Trilobit\AceidBundle\Maintenance\ArticleContentelementIdMaintenance;

$GLOBALS['TL_MAINTENANCE'][] = ArticleContentelementIdMaintenance::class;
