<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_form", target="list.label.label")
 */
class FormLabelCallbackListener
{
    private $translator;
    private $framework;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(array $row, string $label, DataContainer $dc, array $labels): string
    {
        return $label
            .'<span style="color:#A3A3A3;margin-left:3px;padding-left:3px">[ID: '.$row['id'].' / '.$this->translator->trans('MSC.filterRecords', [], 'contao_default').': '.self::getChildRecordsCount($row['id']).']</span>';
    }

    protected static function getChildRecordsCount($pid): string
    {
        return (string) Database::getInstance()
            ->prepare('SELECT count(id) AS count FROM tl_form_field WHERE pid=?')
            ->execute($pid)
            ->count;
    }
}
