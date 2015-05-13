<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticMigrationBundle:Migration:index.html.php');
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered migration-list" id="migrationTable">
            <thead>
            <tr>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'checkall' => 'true',
                    'target'   => '#migrationTable'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'migration',
                    'orderBy'    => 'a.title',
                    'text'       => 'mautic.core.title',
                    'class'      => 'col-migration-title',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'migration',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.core.category',
                    'class'      => 'visible-md visible-lg col-migration-category'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'migration',
                    'orderBy'    => 'a.downloadCount',
                    'text'       => 'mautic.migration.migration.thead.download.count',
                    'class'      => 'visible-md visible-lg col-migration-download-count'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'migration',
                    'orderBy'    => 'a.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'visible-md visible-lg col-migration-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $k => $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                            'item'       => $item,
                            'templateButtons' => array(
                                'edit'       => $security->hasEntityAccess($permissions['migration:migrations:editown'], $permissions['migration:migrations:editother'], $item->getCreatedBy()),
                                'delete'     => $security->hasEntityAccess($permissions['migration:migrations:deleteown'], $permissions['migration:migrations:deleteother'], $item->getCreatedBy()),
                            ),
                            'routeBase'  => 'migration',
                            'langVar'    => 'migration.migration',
                            'nameGetter' => 'getTitle',
                            'customButtons' => array(
                                array(
                                    'attr' => array(
                                        'data-toggle' => 'ajaxmodal',
                                        'data-target' => '#MigrationPreviewModal',
                                        'href' => $view['router']->generate('mautic_migration_action', array('objectAction' => 'preview', 'objectId' => $item->getId()))
                                    ),
                                    'btnText'   => $view['translator']->trans('mautic.migration.migration.preview'),
                                    'iconClass' => 'fa fa-image'
                                )
                            )
                        ));
                        ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array(
                                'item'       => $item,
                                'model'      => 'migration.migration'
                            )); ?>
                            <a href="<?php echo $view['router']->generate('mautic_migration_action',
                                array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                               data-toggle="ajax">
                                <?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)
                            </a>
                            <i class="<?php echo $item->getIconClass(); ?>"></i>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                        <?php $color    = ($category) ? '#' . $category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getDownloadCount(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_migration_index',
            "baseUrl"         => $view['router']->generate('mautic_migration_index'),
            'sessionVar'      => 'migration'
        )); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.migration.noresults.tip')); ?>
<?php endif; ?>

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'MigrationPreviewModal',
    'header' => false
));
