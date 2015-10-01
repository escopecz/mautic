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
$view['slots']->set("headerTitle", $activeMigration->getName());

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'item'       => $activeMigration,
    'customButtons' => array(
        array(
            'attr' => array(
                'href' => $view['router']->generate('mautic_migration_action', array(
                    'objectAction' => 'export',
                    'objectId' => $activeMigration->getId()
                ))
            ),
            'btnText'   => $view['translator']->trans('mautic.migration.migration.export'),
            'iconClass' => 'fa fa-suitcase'
        )
    ),
    'templateButtons' => array(
        'edit'       => $security->hasEntityAccess($permissions['migration:migrations:editown'], $permissions['migration:migrations:editother'], $activeMigration->getCreatedBy()),
        'delete'     => $security->hasEntityAccess($permissions['migration:migrations:deleteown'], $permissions['migration:migrations:deleteother'], $activeMigration->getCreatedBy())
    ),
    'routeBase'  => 'migration',
    'langVar'    => 'migration.migration',
    'nameGetter' => 'getName'
)));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- migration detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $activeMigration->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $activeMigration)); ?>
                    </div>
                </div>
            </div>
            <!--/ migration detail header -->
            <!-- migration detail collapseable -->
            <div class="collapse" id="migration-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $activeMigration)); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ migration detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- migration detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#migration-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ migration detail collapseable toggler -->
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
                        <a href="<?php echo $this->container->get('router')->generate('mautic_migration_action', array('objectAction' => 'download', 'objectId' => $activeMigration->getId())); ?>" class="btn btn-default">
                            <i class="fa fa-download"></i> Download
                        </a>
                    </p>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo $view['translator']->trans('mautic.migration.export.package.does.not.exist'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
    <input id="itemId" type="hidden" value="<?php echo $activeMigration->getId(); ?>" />
</div>
<!--/ end: box layout -->
