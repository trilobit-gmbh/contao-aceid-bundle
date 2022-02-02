<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Date;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_news", target="list.sorting.child_record")
 */
class NewsitemsLabelCallbackListener
{
    private $translator;
    private $framework;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(array $row): string
    {
        $item = new ArticleLabelCallbackListener($this->framework, $this->translator, 'tl_news');
        $childs = $item->__invoke($row, '');
        $childs = preg_replace('/^.*?<a.*?data-previewlink>.*?<\/a>(.*)$/s', '$1', $childs);
        $childs = preg_replace('/^.*?<span.*?data-id>.*?<\/span>(.*)$/s', '$1', $childs);

        return '<div class="tl_content_left">'
            .$row['headline']
            .'<span style="color:#A3A3A3;margin-left:3px;padding-left:3px">[ID: '.$row['id'].' / '.Date::parse(Config::get('datimFormat'), $row['date']).']</span>'
            .$childs
            .'</div>';
    }
}
