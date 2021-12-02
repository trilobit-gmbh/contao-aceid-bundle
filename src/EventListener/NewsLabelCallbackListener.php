<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-aceid-bundle
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_news_archive", target="list.label.label")
 */
class NewsLabelCallbackListener
{
    private $translator;

    public function __construct(?TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(array $row, string $label, DataContainer $dc, array $labels): string
    {
        return $label.' <span style="color: #A3A3A3; padding-left: 3px;">[ID: '.$row['id'].' / '.$this->translator->trans('MSC.filterRecords', [], 'contao_default').': '.self::getChildRecordsCount($row['id']).']';
    }

    protected static function getChildRecordsCount($pid): string
    {
        return Database::getInstance()
            ->prepare('SELECT count(id) AS count FROM tl_news WHERE pid=?')
            ->execute($pid)->count;
    }
}
