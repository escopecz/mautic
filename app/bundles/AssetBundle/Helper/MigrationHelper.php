<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MigrationHelper
 *
 * @package Mautic\AssetBundle\Helper
 */
class MigrationHelper
{

    /**
     * @param  Factory  $factory
     * @param  array    $entities    
     *
     * @return array
     */
    public function export(MauticFactory $factory, array $entities)
    {
        $properties = $action->getProperties();

        $assetId  = $properties['asset'];

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model  = $factory->getModel('asset');
        $asset  = $model->getEntity($assetId);
        $form   = $action->getForm();

        //make sure the asset still exists and is published
        if ($asset != null && $asset->isPublished()) {
            //register a callback after the other actions have been fired
            return array(
                'callback' => '\Mautic\AssetBundle\Helper\FormSubmitHelper::downloadFile',
                'form'     => $form,
                'asset'    => $asset,
                'message'  => $properties['message']
            );
        }
    }

    /**
     * @param  Factory  $factory
     * @param  array    $entities    
     *
     * @return array
     */
    public function countEntities(MauticFactory $factory, array $entities)
    {
        echo "<pre>";var_dump($entities);die("</pre>");
        if (isset($entities)) {
            $factory->getEntityManager()->getRepository('MauticAssetBundle:Download')->getDownloads($assetId, $amount, $unit);
        }
    }
}
