<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'migration');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.migration.migration.export')); // @todo translate this

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-12 bg-white height-auto">
        <div class="bg-auto bg-dark-xs">
            <div class="tab-content pa-md">
                <?php if ($packageInfo['exists']) : ?>
                    <div class="alert alert-success" role="alert">
                        <p>
                            <?php echo $view['translator']->trans('mautic.migration.export.package.info', array(
                                '%path%' => $packageInfo['path'],
                                '%modified%' => $view['date']->toFullConcat($packageInfo['modified']),
                                '%file_size%' => $packageInfo['file_size']
                            )); ?>
                        </p>
                        <p class="text-center">
                            <a href="<?php echo $this->container->get('router')->generate('mautic_migration_action', array('objectAction' => 'download')); ?>" class="btn btn-default">
                                <i class="fa fa-download"></i> Download
                            </a>
                        </p>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo $view['translator']->trans('mautic.migration.export.package.does.not.exist'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($blueprint) : ?>
                    <a class="btn btn-success mt-md" href="<?php echo $view['router']->generate('mautic_migration_action', array('objectAction' => 'export')); ?>" data-toggle="ajax">
                        <?php echo $view['translator']->trans('mautic.migration.migration.export'); ?>
                    </a>
                    <?php if ($blueprint['totalEntities']) : ?>
                        <h4 class="pt-md pb-md">
                            <?php echo $view['translator']->trans('mautic.migration.entity.export.progress'); ?>
                        </h4>
                        <?php $entityProgress = round($blueprint['exportedEntities'] / $blueprint['totalEntities'] * 100); ?>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $entityProgress; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $entityProgress; ?>%;">
                                <?php echo $entityProgress; ?>%
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($blueprint['totalFiles']) : ?>
                        <h4 class="pt-md pb-md">
                            <?php echo $view['translator']->trans('mautic.migration.files.export.progress'); ?>
                        </h4>
                        <?php $FileProgress = round($blueprint['exportedFiles'] / $blueprint['totalFiles'] * 100); ?>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $FileProgress; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $FileProgress; ?>%;">
                                <?php echo $FileProgress; ?>%
                            </div>
                        </div>
                    <?php endif; ?>
                    <pre><?php print_r($blueprint); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!--/ left section -->

    <!-- ToDo: Show the last exports here -->
    <!-- right section -->
    <!-- <div class="col-md-3 bg-white bdr-l height-auto"> -->
        <!-- activity feed -->
        <?php //echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    <!-- </div> -->
    <!--/ right section -->
</div>
<!--/ end: box layout -->
