<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

use Trilobit\AceidBundle\DataContainer\Article;

$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = [Article::class, 'onContentAction'];
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = [Article::class, 'onContentAction'];
