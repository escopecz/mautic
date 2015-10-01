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
                    'orderBy'    => 'a.name',
                    'text'       => 'mautic.core.name',
                    'class'      => 'col-migration-name',
                    'default'    => true
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
                                'clone'      => $permissions['migration:migrations:create']
                            ),
                            'routeBase'  => 'migration',
                            'langVar'    => 'migration.migration',
                            'nameGetter' => 'getName'
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
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                        <?php endif; ?>
                    </td>
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
