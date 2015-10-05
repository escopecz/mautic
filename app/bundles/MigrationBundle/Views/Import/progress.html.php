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
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.migration.import.migrations'));

?>

<div class="row ma-lg">
    <div class="col-sm-offset-3 col-sm-6">
        <div class="panel">
            <div class="panel-heading text-center">
                <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.migration.progress.header'); ?></h4>
            </div>
            <div class="panel-body">
                <h4><?php echo $view['translator']->trans('mautic.migration.import.inprogress'); ?></h4>
                <?php if ($blueprint) : ?>
                    <pre><?php print_r($blueprint) ?></pre>
                <?php endif;?>
            </div>

            <?php if (!empty($stats['failures'])): ?>
                <ul class="list-group">
                    <?php foreach ($stats['failures'] as $lineNumber => $failure): ?>
                        <li class="list-group-item text-left">
                            <a target="_new" class="text-danger">
                                <?php echo "(#$lineNumber) $failure"; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="panel-footer">
                <a class="text-danger mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'import', 'cancel' => 1)); ?>" data-toggle="ajax">
                    <?php echo $view['translator']->trans('mautic.core.form.cancel'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
