<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\DataContainer;

use Contao\Database;
use Contao\DataContainer;

class Article
{
    public static function getChildRecords($pid, bool $getAllData = false): array
    {
        if ($getAllData) {
            return Database::getInstance()
                ->prepare('SELECT * FROM tl_content WHERE pid=? ORDER BY sorting')
                ->execute($pid)
                ->fetchAllAssoc()
            ;
        }

        return Database::getInstance()
            ->prepare('SELECT id FROM tl_content WHERE pid=? ORDER BY sorting')
            ->execute($pid)
            ->fetchEach('id')
        ;
    }

    public static function setChildRecords($pid, array $childs = []): void
    {
        Database::getInstance()
            ->prepare('UPDATE tl_article %s WHERE id=?')
            ->set(['contentElements' => serialize($childs)])
            ->limit(1)
            ->execute($pid)
        ;
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
}
