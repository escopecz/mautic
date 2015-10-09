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
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.migration.migrations'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new'    => $permissions['migration:migrations:create']
    ),
    'customButtons' => array(
        array(
            'attr' => array(
                'href' => $view['router']->generate('mautic_migration_action', array(
                    'objectAction' => 'upload'
                ))
            ),
            'btnText'   => $view['translator']->trans('mautic.migration.upload.btn'),
            'iconClass' => 'fa fa-upload'
        )
    ),
    'routeBase' => 'migration',
    'langVar'   => 'migration.migration'
)));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:bulk_actions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'langVar'     => 'migration.migration',
        'routeBase'   => 'migration',
        'templateButtons' => array(
            'delete' => $permissions['migration:migrations:deleteown'] || $permissions['migration:migrations:deleteother']
        )
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
