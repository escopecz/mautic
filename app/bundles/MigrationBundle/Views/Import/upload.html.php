<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'migrationImport');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.migration.upload'));

?>

<div class="row">
    <div class="col-sm-offset-3 col-sm-6">
        <div class="ml-lg mr-lg mt-md pa-lg">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title"><?php echo $view['translator']->trans('mautic.migration.import.start.instructions'); ?></div>
                </div>
                <div class="panel-body">
                    <?php if ($blueprint): ?>
                        <div class="alert alert-info" role="alert">
                            <?php echo $view['translator']->trans('mautic.migration.import.already.uploaded'); ?>
                            <a class="text-danger mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'import')); ?>" data-toggle="ajax">
                                <?php echo $view['translator']->trans('mautic.migration.view.details'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php echo $view['form']->start($form); ?>
                    <div class="input-group well mt-lg">
                        <?php echo $view['form']->widget($form['file']); ?>
                        <span class="input-group-btn">
                            <?php echo $view['form']->widget($form['start']); ?>
                        </span>
                    </div>
                    <?php echo $view['form']->end($form); ?>
                </div>
            </div>
        </div>
    </div>
</div>
