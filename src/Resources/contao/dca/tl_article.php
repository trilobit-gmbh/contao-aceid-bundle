<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Trilobit\AceidBundle\DataContainer\Article;

PaletteManipulator::create()
    ->addField('contentElements', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');

$GLOBALS['TL_DCA']['tl_article']['fields']['contentElements'] = [
    'search' => true,
    'inputType' => 'checkbox',
    'options_callback' => [Article::class, 'onArticleAction'],
    'eval' => ['multiple' => true, 'readonly' => true, 'disabled' => true, 'tl_class' => 'invisible'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_article']['list']['label']['fields'] = ['title'];
$GLOBALS['TL_DCA']['tl_article']['list']['label']['format'] = '%s';
