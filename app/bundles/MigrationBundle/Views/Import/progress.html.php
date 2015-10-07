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
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.migration.import'));

?>

<div class="row ma-lg">
    <div class="col-sm-offset-3 col-sm-6">
        <div class="panel">
            <div class="panel-heading text-center">
                <h4 class="panel-title"><?php echo $view['translator']->trans('mautic.migration.progress.header'); ?></h4>
            </div>
            <div class="panel-body">
                <?php if ($blueprint) : ?>
                    <?php if (!empty($blueprint['entities'])) : ?>
                        <h4><?php echo $view['translator']->trans('mautic.migration.import.entities'); ?></h4>
                        <?php foreach ($blueprint['entities'] as $entityKey => $entity) : ?>
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <?php echo str_replace('.', ' - ', $entityKey); ?>
                                    <span class="badge pull-right"><?php echo $entity['processed'] ?></span>
                                </div>
                                <?php if (!empty($entity['warning'])) : ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo $entity['warning']; ?>
                                    </div>
                                <?php endif;?>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php else : ?>
                    <a class="text-danger mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'upload')); ?>" data-toggle="ajax">
                        <?php echo $view['translator']->trans('mautic.migration.import.upload.first'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="panel-footer">
                <a class="text-danger mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'upload')); ?>" data-toggle="ajax">
                    <?php echo $view['translator']->trans('mautic.core.form.cancel'); ?>
                </a>
                <a class="btn btn-primary pull-right" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'upload')); ?>" data-toggle="ajax">
                    <?php echo $view['translator']->trans('mautic.migration.import.btn'); ?>
                </a>
            </div>
            <pre><?php print_r($blueprint) ?></pre>
        </div>
    </div>
</div>
