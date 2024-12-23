<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright     (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
/** @var \Joomla\Component\MigrateToJoomla\Administrator\View\Migrate\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useStyle('com_migratetojoomla.migratetojoomla');

?>
<div id="migratetojoomla" class="p-4">
    <form action="<?php echo Route::_('index.php?option=com_migratetojoomla'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
        <?php echo $this->form->renderField('framework'); ?>
        <input type="hidden" name="task" value="">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
