<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

namespace Trilobit\AceidBundle\DataContainer;

use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Article
{
    public static function getChildRecords($pid, bool $getAllData = false): array
    {
        if ($getAllData) {
            return Database::getInstance()
                ->prepare('SELECT * FROM tl_content WHERE pid=?')
                ->execute($pid)
                ->fetchAllAssoc();
        }

        return Database::getInstance()
            ->prepare('SELECT id FROM tl_content WHERE pid=?')
            ->execute($pid)
            ->fetchEach('id');
    }

    public static function setChildRecords($pid, array $childs = []): void
    {
        Database::getInstance()
            ->prepare('UPDATE tl_article %s WHERE id=?')
            ->set(['contentElements' => serialize($childs)])
            ->limit(1)
            ->execute($pid);
    }

    public static function getAndSetChildRecords($pid): array
    {
        $options = self::getChildRecords($pid);
        self::setChildRecords($pid, $options);

        return $options;
    }

    public static function onArticleAction(DataContainer $dc)
    {
        $options = [];

        foreach (self::getChildRecords($dc->id, true) as $option) {
            $options[$option['id']] = $option['type'].' <span style="color: #A3A3A3; padding-left: 3px;">[ID: '.$option['id'].']';
        }

        return $options;
    }

    public static function onContentAction(DataContainer $dc)
    {
        return self::getAndSetChildRecords($dc->activeRecord->pid);
    }

    public static function getChildRecordsList($row, $label)
    {
        Controller::loadLanguageFile('tl_content');

        $image = 'articles';

        $unpublished = ($row['start'] && $row['start'] > time()) || ($row['stop'] && $row['stop'] <= time());

        if ($unpublished || !$row['published']) {
            $image .= '_';
        }

        /** @var AttributeBagInterface $objSessionBag */
        $objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
        $search = $objSessionBag->get('search');

        $filterId = null;
        if (\is_array($search) && !empty($search['tl_article']['value']) && 'contentElements' === $search['tl_article']['field']) {
            $filterId = $search['tl_article']['value'];
        }

        $childs = [];
        foreach (self::getChildRecords($row['id'], true) as $option) {

            $childs[] = '&rarr; '
                .'<a href="contao?do=article&table=tl_content&id='.$option['id'].'&amp;popup=1&amp;nb=1&amp;act=edit&amp;rt='.REQUEST_TOKEN.'" title="'.sprintf($GLOBALS['TL_LANG']['tl_content']['edit'], $option['id']).'" class="edit" onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", 'ID: '.$option['id'])).'\',\'url\':this.href});return false">'
                .'<span style="color:'.(false === strpos($option['id'], $filterId) ? '#A3A3A3':'#444').'">'
                .(!empty($filterId) && $filterId === $option['id'] ? '<span style="font-weight:bold">':'')
                .$option['type']
                .' <span style="color:#A3A3A3;padding:0 12px 0 3px">[ID: '.str_replace($filterId, '<span style="font-weight:bold;color:#444">'.$filterId.'</span>', $option['id']).']</span>'
                .(!empty($filterId) && $filterId === $option['id'] ? '</span>':'')
                .Image::getHtml('edit.svg', sprintf($GLOBALS['TL_LANG']['tl_content']['edit'], $option['id']), 'style="margin-bottom:2px"')
                .'</span>'
                #.(false === strpos($option['id'], $filterId) ? '</span>':'</mark>')
                .'</a>'
            ;
        }

        return '<a href="contao/preview.php?page='.$row['pid'].'&amp;article='.($row['alias'] ?: $row['id']).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['view']).'" target="_blank">'
            .Image::getHtml($image.'.svg', '', 'data-icon="'.($unpublished ? $image : rtrim($image, '_')).'.svg" data-icon-disabled="'.rtrim($image, '_').'_.svg"')
            .'</a> '
            .$label
            .'<div class="" style="cursor:pointer" onclick="'
                .'item=$(\'ace_'.$row['id'].'\');'
                .'image=$(this).getFirst(\'img\');'
                .'if(item.getStyle(\'display\')==\'none\'){'
                    .'item.setStyle(\'display\',\'inline-block\');'
                    .'image.src=AjaxRequest.themePath+\'icons/folMinus.svg\''
                .'}else{'
                    .'item.setStyle(\'display\',\'none\');'
                    .'image.src=AjaxRequest.themePath+\'icons/folPlus.svg\''
                .'}'
            .'">'
            .Image::getHtml('fol'.(!empty($filterId) ? 'Minus' : 'Plus').'.svg', '', 'data-icon="folMinus.svg" data-icon-disabled="folPlus.svg"').' '.$GLOBALS['TL_LANG']['tl_article']['contentElements'][0]
            .'</div>'
            .'<div id="ace_'.$row['id'].'" style="display:'.(!empty($filterId) ? 'block' : 'none').';width:100%;text-indent:0;margin:2px 0 0 2px">'
            .implode('<br>', $childs)
            .'</div>'
            ;
    }
}
