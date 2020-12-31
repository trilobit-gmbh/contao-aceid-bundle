<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

use Trilobit\AceidBundle\DataContainer\Article;

$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = [Article::class, 'onContentAction'];
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = [Article::class, 'onContentAction'];

$GLOBALS['TL_DCA']['tl_content']['list']['label']['fields'][] = 'id';
$GLOBALS['TL_DCA']['tl_content']['list']['label']['format'] = '<span style="color: #A3A3A3; padding-left: 3px;">[ID: %s]';
