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
        <div class="panel">test
            <div class="panel-heading text-center">
                <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.migration.progress.header'); ?></h4>
            </div>
            <div class="panel-body">
                <?php if ($blueprint) : ?>
                    <?php if (!empty($blueprint['entities'])) : ?>
                        <h4><?php echo $view['translator']->trans('mautic.migration.import.entities'); ?></h4>
                        <?php foreach ($blueprint['entities'] as $entityKey => $entity) : ?>
                            <div>
                                <h5>
                                    <?php echo $view['translator']->trans('mautic.migration.entity.' . strtolower($entityKey)); ?>
                                    <span class="badge"><?php echo $entity['processed'] ?></span>
                                </h5>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
                                        60%
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <pre><?php print_r($blueprint) ?></pre>
                <?php else : ?>
                    <a class="text-danger mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'upload')); ?>" data-toggle="ajax">
                        <?php echo $view['translator']->trans('mautic.migration.import.upload.first'); ?>
                    </a>
                <?php endif; ?>
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
