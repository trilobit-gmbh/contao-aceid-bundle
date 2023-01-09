<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\Maintenance;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Trilobit\AceidBundle\DataContainer\Article;

class ArticleContentelementIdMaintenance extends Backend implements \executable
{
    public function isActive(): bool
    {
        return 'refresh_article_contentelements' === Input::post('FORM_SUBMIT');
    }

    /**
     * @return mixed
     */
    public function run()
    {
        /** @var BackendTemplate|object $objTemplate */
        $objTemplate = new BackendTemplate('be_maintenance_refresh_article_contentelements');

        $objTemplate->isActive = $this->isActive();
        $objTemplate->message = Message::generateUnwrapped(__CLASS__);

        if ($this->isActive()) {
            $articles = Database::getInstance()
                ->prepare('SELECT id, title, contentElements FROM tl_article')
                ->execute()
                ->fetchAllAssoc()
            ;

            if (empty($articles)) {
                Message::addError($GLOBALS['TL_LANG']['tl_maintenance']['aceid']['error'], __CLASS__);
                $this->reload();
            }

            foreach ($articles as $article) {
                Article::getAndSetChildRecords($article['id']);
            }

            Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_maintenance']['aceid']['success'], \count($articles)), __CLASS__);
            $this->reload();
        }

        $objTemplate->action = ampersand(Environment::get('request'));
        $objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['aceid']['headline'];
        $objTemplate->description = $GLOBALS['TL_LANG']['tl_maintenance']['aceid']['description'];
        $objTemplate->submit = $GLOBALS['TL_LANG']['tl_maintenance']['aceid']['submit'];

        return $objTemplate->parse();
    }
}
