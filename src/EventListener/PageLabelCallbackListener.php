<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\Backend;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_page", target="list.label.label")
 */
class PageLabelCallbackListener
{
    private $translator;

    /**
     * @var array
     */
    private $layouts;

    public function __construct(?TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->layouts = [];

        $result = Database::getInstance()
            ->execute('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name')
            ->fetchAllAssoc()
        ;

        foreach ($result as $value) {
            $this->layouts[$value['id']] = $value;
            $this->layouts[$value['id']]['output'] = $value['theme'].' > '.$value['name'];
        }
    }

    public function __invoke(array $row, string $label, DataContainer $dc, string $imageAttribute = '', bool $returnImage = false, ?bool $isProtected = null): string
    {
        return Backend::addPageIcon($row, $label, $dc)
            .'&nbsp;<span style="color:#A3A3A3;margin-left:3px;padding-left:3px">[ID: '.$row['id'].(!empty($row['layout']) && !empty($row['includeLayout']) ? ' / '.$this->translator->trans('MOD.design', [], 'contao_default').': '.$this->layouts[$row['layout']]['output'] : '').']';
    }
}
