<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_article", target="list.label.label")
 */
class ArticleLabelCallbackListener
{
    private $translator;
    private $framework;
    private $search;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator, $ptable = 'tl_article')
    {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->ptable = $ptable;

        $this->contaoVersion = (method_exists(ContaoCoreBundle::class, 'getVersion') ? ContaoCoreBundle::getVersion() : VERSION);

        /* @var AttributeBagInterface $objSessionBag */
        if (version_compare($this->contaoVersion, '4.9', '>')) {
            $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
        } else {
            $objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
        }
        $this->search = $objSessionBag->get('search');

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile('tl_content');
        $system->loadLanguageFile('tl_article');
    }

    public function __invoke(array $row, string $label, ?DataContainer $dc = null, string $imageAttribute = '', bool $returnImage = false, ?bool $isProtected = null): string
    {
        $image = 'articles';

        $unpublished = ($row['start'] && $row['start'] > time()) || ($row['stop'] && $row['stop'] <= time());

        if ($unpublished || !$row['published']) {
            $image .= '_';
        }

        $filterId = null;
        if (\is_array($this->search) && !empty($this->search['tl_article']['value']) && 'contentElements' === $this->search['tl_article']['field']) {
            $filterId = $this->search['tl_article']['value'];
        }

        if (version_compare($this->contaoVersion, '4.9', '>')) {
            $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        } else {
            $requestToken = REQUEST_TOKEN;
        }

        $childs = [];
        foreach (self::getChildRecords($row['id'], $this->ptable) as $option) {
            $childs[] = '&rarr; '
                .'<a href="contao?do=article&table=tl_content&id='.$option['id'].'&amp;popup=1&amp;nb=1&amp;act=edit&amp;rt='.$requestToken.'" title="'.sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']).'" class="edit" onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", 'ID: '.$option['id'])).'\',\'url\':this.href});return false">'
                .'<span style="color:'.(!empty($filterId) && false === strpos((string) $option['id'], (string) $filterId) ? '#A3A3A3' : '#444').'">'
                .(!empty($filterId) && $filterId === $option['id'] ? '<span style="font-weight:bold">' : '')
                .$this->translator->trans('CTE.'.$option['type'].'.0', [], 'contao_default')
                .' <span style="color:#A3A3A3;padding:0 12px 0 3px">[ID: '.str_replace((string) $filterId, '<span style="font-weight:bold;color:#444">'.$filterId.'</span>', (string) $option['id']).']</span>'
                .(!empty($filterId) && $filterId === $option['id'] ? '</span>' : '')
                .Image::getHtml('edit.svg', sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']), 'style="margin-bottom:2px"')
                .'</span>'
                .'</a>'
            ;
        }

        $data = '<a href="contao/preview.php?page='.$row['pid'].'&amp;article='.($row['alias'] ?: $row['id']).'" title="'.StringUtil::specialchars($this->translator->trans('MSC.view', [], 'contao_default')).'" target="_blank" data-previewlink>'
            .Image::getHtml($image.'.svg', '', 'data-icon="'.($unpublished ? $image : rtrim($image, '_')).'.svg" data-icon-disabled="'.rtrim($image, '_').'_.svg"')
            .'</a>&nbsp;'
            .$label
            .'<span style="color:#A3A3A3;margin-left:3px;padding-left:3px" data-id>'
            .'[ID: '.$row['id']
            .(!empty($row['inColumn']) ? ' / '.$this->translator->trans('tl_article.inColumn.0', [], 'contao_default').': '.$this->translator->trans('COLS.'.$row['inColumn'], [], 'contao_default') : '')
            .']'
            .'</span>'
            ;

        $hintChilds = '<span style="color:#A3A3A3;margin-left:3px;padding-left:3px">['.$this->translator->trans('MSC.filterRecords', [], 'contao_default').': '.\count($childs).']</span>';

        if (0 === \count($childs)) {
            $data .= '<div style="margin-top:2px;margin-left:22px">'
                .'<div style="width:10px;height:10px;display:inline-block;border:1px solid #ccc;border-radius:4px;margin:3px;vertical-align:middle;line-height:.6;text-align:center;color:#335f7f">·</div> '
                .$this->translator->trans('tl_article.contentElements.0', [], 'contao_default')
                .$hintChilds
                .'</div>'
            ;
        } else {
            $data .= '<div style="cursor:pointer;margin-top:2px;margin-left:22px" onclick="'
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
                .Image::getHtml('fol'.(!empty($filterId) ? 'Minus' : 'Plus').'.svg', '', 'data-icon="folMinus.svg" data-icon-disabled="folPlus.svg" style="margin-right:2px"')
                .' '.$this->translator->trans('tl_article.contentElements.0', [], 'contao_default')
                .$hintChilds
                .'</div>'
                .'<div id="ace_'.$row['id'].'" style="display:'.(!empty($filterId) ? 'block' : 'none').';width:100%;text-indent:0;margin:2px 0 0 44px">'
                .implode('<br>', $childs)
                .'</div>'
            ;
        }

        return $data;
    }

    protected static function getChildRecords($pid, $ptable): array
    {
        return Database::getInstance()
            ->prepare('SELECT * FROM tl_content WHERE pid=? AND ptable=?')
            ->execute($pid, $ptable)
            ->fetchAllAssoc()
        ;
    }
}
