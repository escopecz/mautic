<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\MigrationBundle\Event\MigrationImportEvent;
use Mautic\MigrationBundle\Event\MigrationImportViewEvent;
use Mautic\MigrationBundle\MigrationEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MigrationController
 */
class MigrationController extends FormController
{

    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $model       = $this->factory->getModel('migration.migration');
        $security    = $this->factory->getSecurity();
        $blueprint   = $model->getBlueprint();

        if ($blueprint === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $this->generateUrl('mautic_migration_index'),
                'viewParameters'  => array('blueprint' => $blueprint),
                'contentTemplate' => 'MauticMigrationBundle:Migration:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_migration_index',
                    'mauticContent' => 'migration'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.migration.migration.error.notfound',
                        'msgVars' => array('%path%' => $model->getExportDir()) // @todo translate this
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->isGranted('migration:migrations:view')) {
            return $this->accessDenied();
        }

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_migration_index'),
            'viewParameters'  => array(
                'blueprint'   => $blueprint,
                'permissions' => $security->isGranted(array(
                    'migration:migrations:viewown'
                ), "RETURN_ARRAY"),
                'security'         => $security,
                'packageInfo'      => $model->getLastPackageInfo(),
            ),
            'contentTemplate' => 'MauticMigrationBundle:Migration:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_migration_index',
                'mauticContent' => 'migration'
            )
        ));
    }

    /**
     * Export a migration
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function exportAction ()
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model  = $this->factory->getModel('migration.migration');

        if (!$this->factory->getSecurity()->isGranted('migration:migrations:edit')) {
            return $this->accessDenied();
        }

        $blueprint = $model->triggerExport();

        return $this->indexAction();
    }

    /**
     * Download exported zip package
     *
     * @return void
     */
    public function downloadAction()
    {
        //find the asset
        $security = $this->factory->getSecurity();

        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model    = $this->factory->getModel('migration.migration');

        //make sure the user can view the migration or deny access
        if (!$security->isGranted('migration:migrations:view')) {
            return $this->accessDenied();
        }

        $path = $model->getZipPackagePath();

        if (file_exists($path)) {
            $contents = file_get_contents($path);
        } else {
            return $this->notFound();
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        
        $stream = $this->request->get('stream', 0);

        if (!$stream) {
            $response->headers->set('Content-Disposition', 'attachment;filename="export.zip');
        }

        $response->setContent($contents);

        return $response;

        $this->notFound();
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model  = $this->factory->getModel('migration.migration');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('migration:migrations:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'migration:migrations:viewown', 'migration:migrations:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.migration.page', 1);
        $returnUrl = $this->generateUrl('mautic_migration_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticMigrationBundle:Migration:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_migration_index',
                'mauticContent' => 'migration'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
            $model  = $this->factory->getModel('migration.migration');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.migration.migration.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'migration:migrations:deleteown',
                'migration:migrations:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'migration.migration');
            }

            $model->removeExportedFiles($entity->getId());
            $model->deleteEntity($entity);

            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $entity->getName(),
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction ()
    {
        $page      = $this->factory->getSession()->get('mautic.migration.page', 1);
        $returnUrl = $this->generateUrl('mautic_migration_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticMigrationBundle:Migration:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_migration_index',
                'mauticContent' => 'migration'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
            $model     = $this->factory->getModel('migration');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.migration.migration.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                    'migration:migrations:deleteown', 'migration:migrations:deleteother', $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'migration', true);
                } else {
                    $deleteIds[] = $objectId;
                    $model->removeExportedFiles($objectId);
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.migration.migration.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * @param int  $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction($objectId = 0)
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model   = $this->factory->getModel('migration.migration');

        $action    = $this->generateUrl('mautic_migration_action', array('objectAction' => 'upload'));
        $form      = $this->get('form.factory')->create('migration_upload', array(), array('action' => $action));
        $importDir = $model->getImportDir();
        $username  = $this->factory->getUser()->getUsername();
        $fileName  = $username . '_migration_import.zip';
        $zipPath   = $this->factory->getParameter('import_dir') . '/' . $fileName;

        // Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {

                    // Remove previously uzipped files
                    if (file_exists($importDir)) {
                        $model->deleteFolderRecursivly($importDir);
                    }

                    // Remove previously uploaded zip file
                    if (file_exists($zipPath)) {
                        unlink($zipPath);
                    }

                    // Make the import dir if doesn't exist
                    if (!file_exists($importDir)) {
                        mkdir($importDir, 0755, true);
                    }

                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        try {
                            $fileData->move($this->factory->getParameter('import_dir'), $fileName);

                            $zip = new \ZipArchive;
                            $res = $zip->open($zipPath);
                            if ($res === TRUE) {
                                // extract it to the path we determined above
                                $zip->extractTo($importDir);
                                $zip->close();
                                return $this->importAction(0);
                            } else {
                                $form->addError(
                                    new FormError(
                                        $this->factory->getTranslator()->trans('mautic.migration.upload.couldnotunzip', array(), 'validators')
                                    )
                                );
                            }
                        } catch (\Exception $e) {
                        }

                        $form->addError(
                            new FormError(
                                $this->factory->getTranslator()->trans('mautic.migration.upload.filenotreadable', array(), 'validators')
                            )
                        );
                    }

                    $form->addError(
                        new FormError(
                            $this->factory->getTranslator()->trans('mautic.migration.upload.filenotfound', array(), 'validators')
                        )
                    );

                    return $this->uploadAction();
                }
            }
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'form' => $form->createView(),
                    'blueprint' => $model->getImportedBlueprint()
                ),
                'contentTemplate' => 'MauticMigrationBundle:Import:upload.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_migration_index',
                    'mauticContent' => 'migrationImport',
                    'route'         => $this->generateUrl(
                        'mautic_migration_action',
                        array(
                            'objectAction' => 'import'
                        )
                    )
                )
            )
        );
    }

    /**
     * @param int  $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function importAction($objectId = 0)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', true);

        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model   = $this->factory->getModel('migration.migration');
        $session = $this->factory->getSession();

        if (!$this->factory->getSecurity()->isGranted('migration:migrations:create')) {
            return $this->accessDenied();
        }

        $blueprint  = $model->getImportedBlueprint();

        if (is_array($blueprint)) {
            $dispatcher = $this->factory->getDispatcher();
            $event      = new MigrationImportViewEvent($blueprint);
            $dispatcher->dispatch(MigrationEvents::MIGRATION_IMPORT_PROGRESS_ON_GENERATE, $event);
            $blueprint  = $event->getBlueprint();
        }

        $action     = $this->generateUrl('mautic_migration_action', array('objectAction' => 'import'));
        $form       = $this->get('form.factory')->create('migration_import', array(), array('action' => $action, 'blueprint' => $blueprint));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $model->triggerImport($blueprint, $form->getData());
                }
            } else {
                return $this->importAction();
            }
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'blueprint' => $blueprint,
                    'form'      => $form->createView()
                ),
                'contentTemplate' => 'MauticMigrationBundle:Import:import.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_migration_index',
                    'mauticContent' => 'migrationImport',
                    'route'         => $this->generateUrl(
                        'mautic_migration_action',
                        array(
                            'objectAction' => 'import'
                        )
                    )
                )
            )
        );
    }
}
