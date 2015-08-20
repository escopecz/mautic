<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$header = ($activeMigration->getId()) ?
    $view['translator']->trans('mautic.migration.migration.edit', array('%name%' => $activeMigration->getName())) :
    $view['translator']->trans('mautic.migration.migration.new');
$view['slots']->set("headerTitle", $header);
$view['slots']->set('mauticContent', 'migration');
?>
<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['name']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <?php echo $view['form']->row($form['description']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <?php echo $view['form']->row($form['entities']); ?>
                </div>
                <div class="col-xs-6">
                    <?php echo $view['form']->row($form['folders']); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <?php echo $view['form']->row($form['properties']); ?>
                </div>
            </div>
        </div>
    </div>
     <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>
