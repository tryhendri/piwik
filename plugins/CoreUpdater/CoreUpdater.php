<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreUpdater;

use Exception;
use Piwik\Access;
use Piwik\Common;
use Piwik\Filesystem;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\UpdateCheck;
use Piwik\Updater;
use Piwik\UpdaterErrorException;
use Piwik\Version;

/**
 *
 */
class CoreUpdater extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'Request.dispatchCoreAndPluginUpdatesScreen' => 'dispatch',
            'Platform.initialized'                       => 'updateCheck',
        );
    }

    /**
     * @deprecated
     */
    public static function updateComponents(Updater $updater, $componentsWithUpdateFile)
    {
        return $updater->updateComponents($componentsWithUpdateFile);
    }

    /**
     * @deprecated
     */
    public static function getComponentUpdates(Updater $updater)
    {
        return $updater->getComponentUpdates();
    }

    public function dispatch()
    {
        $module = Common::getRequestVar('module', '', 'string');
        $action = Common::getRequestVar('action', '', 'string');

        $updater = new Updater();
        $updates = $updater->getComponentsWithNewVersion(array('core' => Version::VERSION));
        if (!empty($updates)) {
            Filesystem::deleteAllCacheOnUpdate();
        }
        if ($updater->getComponentUpdates() !== null
            && $module != 'CoreUpdater'
            // Proxy module is used to redirect users to piwik.org, should still work when Piwik must be updated
            && $module != 'Proxy'
            // Do not show update page during installation.
            && $module != 'Installation'
            && !($module == 'LanguagesManager'
                && $action == 'saveLanguage')
        ) {
            if (FrontController::shouldRethrowException()) {
                throw new Exception("Piwik and/or some plugins have been upgraded to a new version. \n" .
                    "--> Please run the update process first. See documentation: http://piwik.org/docs/update/ \n");
            } else {
                Piwik::redirectToModule('CoreUpdater');
            }
        }
    }

    public function updateCheck()
    {
        UpdateCheck::check();
    }
}
