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
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Contao\CoreBundle\DependencyInjection\Attribute\AsCallback(table: 'tl_article', target: 'list.label.label')]
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
        if (version_compare($this->contaoVersion, '4.9', '>')) {
            $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        } else {
            $requestToken = REQUEST_TOKEN;
        }

        $unpublished = ($row['start'] && $row['start'] > time())
            || ($row['stop'] && $row['stop'] <= time())
            || !$row['published'];

        $filterId = null;
        $filterFound = false;
        if (\is_array($this->search) && !empty($this->search['tl_article']['value']) && 'contentElements' === $this->search['tl_article']['field']) {
            $filterId = (string) $this->search['tl_article']['value'];
        }

        $elements = [];
        foreach (self::getChildRecords($row['id'], $this->ptable) as $option) {
            $tmp = '<div class="tl_left with-offset" style="--level:2; padding-top:calc(var(--row-padding) * .5)">'
                .'<span'.(null === $filterId || !\is_int(strpos((string) $option['id'], $filterId)) ? ' class="label-info"' : '').'>&rdca;</span>';

            $tmp .= '<a href="contao?do=article&table=tl_content&id='.$option['id'].'&amp;popup=1&amp;nb=1&amp;act=edit&amp;rt='.$requestToken.'" title="'.\sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']).'" class="edit" onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", 'ID: '.$option['id'])).'\',\'url\':this.href});return false">';

            if (null === $filterId || !\is_int(strpos((string) $option['id'], $filterId))) {
                $tmp .= '<span class="label-info">';
            }

            $tmp .= $this->translator->trans('CTE.'.$option['type'].'.0', [], 'contao_default');

            if (null === $filterId || !\is_int(strpos((string) $option['id'], $filterId))) {
                $tmp .= '</span>';
            }

            $tmp .= '</a>';

            $tmp .= '<span class="label-info">['
                .'ID: ';

            if (null === $filterId || !\is_int(strpos((string) $option['id'], $filterId))) {
                $tmp .= $option['id'];
            } else {
                $tmp .= str_replace((string) $filterId, '<span style="color:var(--text)">'.$filterId.'</span>', (string) $option['id']);
                $filterFound = true;
            }

            $tmp .= ']</span>'
                .'<a href="contao?do=article&table=tl_content&id='.$option['id'].'&amp;popup=1&amp;nb=1&amp;act=edit&amp;rt='.$requestToken.'" title="'.\sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']).'" class="edit" onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", 'ID: '.$option['id'])).'\',\'url\':this.href});return false">'
                .Image::getHtml('edit.svg', \sprintf($this->translator->trans('tl_content.edit', [], 'contao_default'), $option['id']), 'style="margin-bottom:2px"')
                .'</a>';
            $tmp .= '</div>';

            $elements[] = $tmp;
        }

        $buffer = ''
            .'<div>'
                .'<div class="tl_left with-offset" style="--level:0">'
                    .'<a href="contao/preview.php?page='.$row['pid'].'&amp;article='.($row['alias'] ?: $row['id']).'" title="'.StringUtil::specialchars($this->translator->trans('MSC.view', [], 'contao_default')).'" target="_blank" data-previewlink>'
                        .Image::getHtml('articles'.($unpublished ? '_1' : '').'.svg', '', 'data-icon="articles.svg" data-icon-disabled="articles_1.svg"')
                    .'</a>'

                    .$label

                    .'<span class="label-info">['
                        .'ID: '.$row['id']
                            .(!empty($row['inColumn'])
                                ? ' / '.$this->translator->trans('tl_article.inColumn.0', [], 'contao_default').': '.$this->translator->trans('COLS.'.$row['inColumn'], [], 'contao_default')
                                : ''
                            )
                    .']</span>'
                .'</div>';

        if (!empty(\count($elements))) {
            $buffer .= ''
                .'<div class="tl_left with-offset" style="--level:1; padding-top:calc(var(--row-padding) * .5)">'
                    .'<a href="/contao?do=article" data-contao--toggle-nodes-target="toggle" data-action="contao--toggle-nodes#toggle:prevent" data-contao--toggle-nodes-id-param="tl_article_tl_content_tree_'.$row['id'].'" data-contao--toggle-nodes-level-param="1">'
                        .Image::getHtml('fol'.($filterFound ? 'Minus' : 'Plus').'.svg', '', 'data-icon="folMinus.svg" data-icon-disabled="folPlus.svg"')
                        .'<span>'.$this->translator->trans('tl_article.contentElements.0', [], 'contao_default').'</span>'
                    .'</a>'

                    .'<span class="label-info">['
                        .$this->translator->trans('MSC.filterRecords', [], 'contao_default').': '.\count($elements)
                    .']</span>'
                .'</div>'

                .'<div id="tl_article_tl_content_tree_'.$row['id'].'" data-contao--toggle-nodes-target="child" style="'.(!$filterFound ? 'display:none' : '').'">'
                    .implode('', $elements)
                .'</div>';
        }

        $buffer .= '</div>';

        return $buffer;
    }

    protected static function getChildRecords($pid, $ptable): array
    {
        return Database::getInstance()
            ->prepare('SELECT * FROM tl_content WHERE pid=? AND ptable=? ORDER BY sorting')
            ->execute($pid, $ptable)
            ->fetchAllAssoc()
        ;
    }
}
