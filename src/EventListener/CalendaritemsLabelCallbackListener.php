<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\EventListener;

use Contao\Calendar;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Date;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_calendar_events", target="list.sorting.child_record")
 */
class CalendaritemsLabelCallbackListener
{
    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(array $row): string
    {
        $span = Calendar::calculateSpan($row['startTime'], $row['endTime']);

        if ($span > 0) {
            $date = Date::parse(Config::get(($row['addTime'] ? 'datimFormat' : 'dateFormat')), $row['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get(($row['addTime'] ? 'datimFormat' : 'dateFormat')), $row['endTime']);
        } elseif ($row['startTime'] === $row['endTime']) {
            $date = Date::parse(Config::get('dateFormat'), $row['startTime']).($row['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $row['startTime']) : '');
        } else {
            $date = Date::parse(Config::get('dateFormat'), $row['startTime']).($row['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $row['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get('timeFormat'), $row['endTime']) : '');
        }

        $item = new ArticleLabelCallbackListener($this->framework, $this->translator, 'tl_calendar_events');
        $childs = $item->__invoke($row, '');
        $childs = preg_replace('/^.*?<a.*?data-previewlink>.*?<\/a>(.*)$/s', '$1', $childs);
        $childs = preg_replace('/^.*?<span.*?data-id>.*?<\/span>(.*)$/s', '$1', $childs);

        return '<div class="tl_content_left">'
            .$row['title']
            .'<span style="color:#A3A3A3;margin-left:3px;padding-left:3px">[ID: '.$row['id'].' / '.$date.']</span>'
            .$childs
            .'</div>';
    }
}
