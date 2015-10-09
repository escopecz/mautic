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
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($page = 1)
    {

        $model = $this->factory->getModel('migration.migration');

        // set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'migration:migrations:viewown',
            'migration:migrations:viewother',
            'migration:migrations:create',
            'migration:migrations:editown',
            'migration:migrations:editother',
            'migration:migrations:deleteown',
            'migration:migrations:deleteother'
        ), "RETURN_ARRAY");


        if (!$permissions['migration:migrations:viewown'] && !$permissions['migration:migrations:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.migration.limit', $this->factory->getParameter('default_migrationlimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.migration.filter', ''));
        $this->factory->getSession()->set('mautic.migration.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['migration:migrations:viewother']) {
            $filter['force'][] =
                array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $orderBy    = $this->factory->getSession()->get('mautic.migration.orderby', 'm.name');
        $orderByDir = $this->factory->getSession()->get('mautic.migration.orderbydir', 'DESC');

        $migrations = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($migrations);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current migration so redirect to the last migration
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.migration.migration', $lastPage);
            $returnUrl = $this->generateUrl('mautic_migration_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('migration' => $lastPage),
                'contentTemplate' => 'MauticMigrationBundle:Migration:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_migration_index',
                    'mauticContent' => 'migration'
                )
            ));
        }

        //set what migration currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.migration.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $migrations,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'page'        => $page,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticMigrationBundle:Migration:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_migration_index',
                'mauticContent' => 'migration',
                'route'         => $this->generateUrl('mautic_migration_index', array('page' => $page))
            )
        ));
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        $model       = $this->factory->getModel('migration.migration');
        $security    = $this->factory->getSecurity();
        $activeMigration = $model->getEntity($objectId);
        $request     = $this->request;

        //set the migration we came from
        $page = $this->factory->getSession()->get('mautic.migration.page', 1);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'details') : 'details';

        if ($activeMigration === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_migration_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticMigrationBundle:Migration:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_migration_index',
                    'mauticContent' => 'migration'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.migration.migration.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess('migration:migrations:viewown', 'migration:migrations:viewother', $activeMigration->getCreatedBy())) {
            return $this->accessDenied();
        }

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('migration', $activeMigration->getId());

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_migration_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activeMigration->getId())
            ),
            'viewParameters'  => array(
                'activeMigration'  => $activeMigration,
                'permissions'      => $security->isGranted(array(
                    'migration:migrations:viewown',
                    'migration:migrations:viewother',
                    'migration:migrations:create',
                    'migration:migrations:editown',
                    'migration:migrations:editother',
                    'migration:migrations:deleteown',
                    'migration:migrations:deleteother',
                    'migration:migrations:publishown',
                    'migration:migrations:publishother'
                ), "RETURN_ARRAY"),
                'security'         => $security,
                'logs'             => $logs,
                'packageInfo'      => $model->getLastPackageInfo($activeMigration->getId()),
                'blueprint'        => $model->getExistingBlueprint($activeMigration)
            ),
            'contentTemplate' => 'MauticMigrationBundle:Migration:' . $tmpl . '.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_migration_index',
                'mauticContent' => 'migration'
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model = $this->factory->getModel('migration.migration');

        /** @var \Mautic\MigrationBundle\Entity\Migration $entity */
        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->factory->getSession();

        if (!$this->factory->getSecurity()->isGranted('migration:migrations:create')) {
            return $this->accessDenied();
        }

        $maxSize    = $this->factory->getParameter('max_size');
        $extensions = '.' . implode(', .', $this->factory->getParameter('allowed_extensions'));

        $maxSizeError = $this->get('translator')->trans('mautic.migration.migration.error.file.size', array(
            '%fileSize%' => '{{filesize}}',
            '%maxSize%'  => '{{maxFilesize}}'
        ), 'validators');

        $extensionError = $this->get('translator')->trans('mautic.migration.migration.error.file.extension.js', array(
            '%extensions%' => $extensions
        ), 'validators');

        // Set the page we came from
        $page   = $session->get('mautic.migration.page', 1);
        $action = $this->generateUrl('mautic_migration_action', array('objectAction' => 'new'));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_migration_index',
                        '%url%'       => $this->generateUrl('mautic_migration_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));

                    if (!$form->get('buttons')->get('save')->isClicked()) {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }

                    $viewParameters = array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId()
                    );
                    $returnUrl      = $this->generateUrl('mautic_migration_action', $viewParameters);
                    $template       = 'MauticMigrationBundle:Migration:view';
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_migration_index', $viewParameters);
                $template       = 'MauticMigrationBundle:Migration:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_migration_index',
                        'mauticContent' => 'migration'
                    )
                ));
            }
        }

        // Check for integrations to cloud providers
        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');

        $integrations = $integrationHelper->getIntegrationObjects(null, array('cloud_storage'));

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'             => $form->createView(),
                'activeMigration'  => $entity
            ),
            'contentTemplate' => 'MauticMigrationBundle:Migration:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_migration_index',
                'mauticContent' => 'migration',
                'route'         => $this->generateUrl('mautic_migration_action', array(
                    'objectAction' => 'new'
                ))
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model = $this->factory->getModel('migration.migration');

        /** @var \Mautic\MigrationBundle\Entity\Migration $entity */
        $entity     = $model->getEntity($objectId);
        $session    = $this->factory->getSession();
        $page       = $this->factory->getSession()->get('mautic.migration.page', 1);
        $method     = $this->request->getMethod();

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_migration_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticMigrationBundle:Migration:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_migration_index',
                'mauticContent' => 'migration'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.migration.migration.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'migration:migrations:viewown', 'migration:migrations:viewother', $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'migration.migration');
        }

        //Create the form
        $action = $this->generateUrl('mautic_migration_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    //remove the migration from request
                    $this->request->files->remove('migration');

                    $this->addFlash('mautic.core.notice.updated', array(
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_migration_index',
                        '%url%'       => $this->generateUrl('mautic_migration_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));

                    $returnUrl  = $this->generateUrl('mautic_migration_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId()
                    ));
                    $viewParams = array('objectId' => $entity->getId());
                    $template   = 'MauticMigrationBundle:Migration:view';
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl  = $this->generateUrl('mautic_migration_index', array('page' => $page));
                $viewParams = array('page' => $page);
                $template   = 'MauticMigrationBundle:Migration:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => $template
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'             => $form->createView(),
                'activeMigration'  => $entity,
            ),
            'contentTemplate' => 'MauticMigrationBundle:Migration:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_migration_index',
                'mauticContent' => 'migration',
                'route'         => $this->generateUrl('mautic_migration_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * Export a migration
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function exportAction ($objectId)
    {
        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model  = $this->factory->getModel('migration.migration');

        /** @var \Mautic\MigrationBundle\Entity\Migration $entity */
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->hasEntityAccess(
                    'migration:migrations:viewown', 'migration:migrations:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $blueprint = $model->triggerExport($entity);
        }

        return $this->viewAction($objectId);
    }

    /**
     * Download exported zip package
     *
     * @param  int $id
     *
     * @return void
     */
    public function downloadAction($id)
    {
        //find the asset
        $security   = $this->factory->getSecurity();

        /** @var \Mautic\MigrationBundle\Model\MigrationModel $model */
        $model      = $this->factory->getModel('migration.migration');

        /** @var \Mautic\MigrationBundle\Entity\Migration $entity */
        $entity     = $model->getEntity($id);

        if (!empty($entity)) {
            $published    = $entity->isPublished();

            //make sure the user can view the migration or deny access
            if (!$security->hasEntityAccess('migration:migrations:viewown', 'migration:migrations:viewother', $entity->getCreatedBy())) {
                return $this->accessDenied();
            }

            $path = $model->getZipPackagePath($entity->getId());

            if (file_exists($path)) {
                $contents = file_get_contents($path);
            } else {
                return $this->notFound();
            }

            $response = new Response();
            $response->headers->set('Content-Type', 'application/zip');

            $stream = $this->request->get('stream', 0);
            if (!$stream) {
                $response->headers->set('Content-Disposition', 'attachment;filename="export_' . $entity->getId() . '.zip');
            }
            $response->setContent($contents);

            return $response;

        }

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
