<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_article", target="list.label.label")
 */
class ArticleLabelCallbackListener
{
    private $translator;
    private $framework;

    public function __construct(ContaoFramework $framework, ?TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->framework = $framework;

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile('tl_content');
    }

    public function __invoke(array $row, string $label, DataContainer $dc, string $imageAttribute = '', bool $returnImage = false, ?bool $isProtected = null): string
    {
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
        foreach (self::getChildRecords($row['id']) as $option) {
            $childs[] = '&rarr; '
                .'<a href="contao?do=article&table=tl_content&id='.$option['id'].'&amp;popup=1&amp;nb=1&amp;act=edit&amp;rt='.REQUEST_TOKEN.'" title="'.sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']).'" class="edit" onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", 'ID: '.$option['id'])).'\',\'url\':this.href});return false">'
                .'<span style="color:'.(false === strpos($option['id'], $filterId) ? '#A3A3A3' : '#444').'">'
                .(!empty($filterId) && $filterId === $option['id'] ? '<span style="font-weight:bold">' : '')
                .$this->translator->trans('CTE.'.$option['type'].'.0', [], 'contao_default')
                .' <span style="color:#A3A3A3;padding:0 12px 0 3px">[ID: '.str_replace($filterId, '<span style="font-weight:bold;color:#444">'.$filterId.'</span>', $option['id']).']</span>'
                .(!empty($filterId) && $filterId === $option['id'] ? '</span>' : '')
                .Image::getHtml('edit.svg', sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']), 'style="margin-bottom:2px"')
                .'</span>'
                .'</a>'
            ;
        }

        return '<a href="contao/preview.php?page='.$row['pid'].'&amp;article='.($row['alias'] ?: $row['id']).'" title="'.StringUtil::specialchars($this->translator->trans('MSC.view', [], 'contao_default')).'" target="_blank">'
            .Image::getHtml($image.'.svg', '', 'data-icon="'.($unpublished ? $image : rtrim($image, '_')).'.svg" data-icon-disabled="'.rtrim($image, '_').'_.svg"')
            .'</a> '
            .$label.' <span style="color: #A3A3A3; padding-left: 3px;">[ID: '.$row['id'].' / '.$this->translator->trans('tl_article.inColumn.0', [], 'contao_default').': '.$this->translator->trans('COLS.'.$row['inColumn'], [], 'contao_default').']</span>'
            .'<div class="" style="cursor:pointer;margin-left:22px" onclick="'
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
            .Image::getHtml('fol'.(!empty($filterId) ? 'Minus' : 'Plus').'.svg', '', 'data-icon="folMinus.svg" data-icon-disabled="folPlus.svg"').' '.$this->translator->trans('tl_article.contentElements.0', [], 'contao_default')
            .'</div>'
            .'<div id="ace_'.$row['id'].'" style="display:'.(!empty($filterId) ? 'block' : 'none').';width:100%;text-indent:0;margin:2px 0 0 44px">'
            .implode('<br>', $childs)
            .'</div>'
            ;
    }

    protected static function getChildRecords($pid): array
    {
        return Database::getInstance()
            ->prepare('SELECT * FROM tl_content WHERE pid=?')
            ->execute($pid)
            ->fetchAllAssoc();
    }
}
