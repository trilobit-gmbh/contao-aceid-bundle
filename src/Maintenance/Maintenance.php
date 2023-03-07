<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\AceidBundle\Maintenance;

use Contao\Backend;

$contaoVersion = (method_exists(\Contao\CoreBundle\ContaoCoreBundle::class, 'getVersion') ? \Contao\CoreBundle\ContaoCoreBundle::getVersion() : VERSION);

if (version_compare($contaoVersion, '4.9', '>')) {
    abstract class Maintenance extends Backend implements \Contao\MaintenanceModuleInterface
    {
    }
} else {
    abstract class Maintenance extends Backend implements \executable
    {
    }
}
