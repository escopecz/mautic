<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportMigrationCommand
 */
class ExportMigrationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:migrations:export')
            ->setAliases(
                array(
                    'mautic:migration:export'
                )
            )
            ->setDescription('Trigger migration exports for published migration templates.')
            ->addOption(
                '--id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Export a specific migration template.  Otherwise, all migration templates will be exported.',
                null
            )
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of entities to process per round. Defaults to 100.', 100)
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory = $container->get('mautic.factory');

        /** @var \Mautic\MigrationBundle\Model\ExportModel $model */
        $migrationModel = $factory->getModel('migration.migration');

        $translator    = $factory->getTranslator();
        $em            = $factory->getEntityManager();

        // Set SQL logging to null or else will hit memory limits in dev for sure
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $id           = $input->getOption('id');
        $batch        = $input->getOption('batch-limit');
        $force        = $input->getOption('force');

        // Prevent script overlap
        $checkFile      = $checkFile = $container->getParameter('kernel.cache_dir') . '/../script_executions.json';
        $command        = 'mautic:migrations:export';
        $key            = ($id) ? $id : 'all';
        $executionTimes = array();

        if (file_exists($checkFile)) {
            // Get the time in the file
            $executionTimes = json_decode(file_get_contents($checkFile), true);
            if (!is_array($executionTimes)) {
                $executionTimes = array();
            }

            if ($force || empty($executionTimes['in_progress'][$command][$key])) {
                // Just started
                $executionTimes['in_progress'][$command][$key] = time();
            } else {
                // In progress
                $check = $executionTimes['in_progress'][$command][$key];

                if ($check + 1800 <= time()) {
                    // Has been 30 minutes so override
                    $executionTimes['in_progress'][$command][$key] = time();
                } else {
                    $output->writeln('<error>Script in progress. Use -f or --force to force execution.</error>');

                    return 0;
                }
            }
        } else {
            // Just started
            $executionTimes['in_progress'][$command][$key] = time();
        }

        file_put_contents($checkFile, json_encode($executionTimes));

        if ($id) {
            /** @var \Mautic\MigrationBundle\Entity\Migration $migration */
            $migration = $migrationModel->getEntity($id);

            if ($migration !== null && $migration->isPublished()) {
                $progressFolder = $container->getParameter('kernel.cache_dir') . '/migration/export';
                $progressFile = $progressFolder . '/' . $id . '.json';
                $progress = array();
                if (file_exists($progressFile)) {
                    // Get the progress in the file
                    $progress = json_decode(file_get_contents($progressFile), true);

                }
                $output->writeln('<info>'.$translator->trans('mautic.migration.export.starting', array('%id%' => $id)).'</info>');
                $progress = $migrationModel->triggerExport($migration, $progress, $batch, $output);
                // $output->writeln(
                //     '<comment>'.$translator->trans('mautic.migration.trigger.events_executed', array('%events%' => $processed)).'</comment>'."\n"
                // );

                if (!is_dir($progressFolder)) {
                    mkdir($progressFolder, 0775, true);
                }

                file_put_contents($progressFile, json_encode($progress));

            } else {
                $output->writeln('<error>'.$translator->trans('mautic.migration.template.not_found', array('%id%' => $id)).'</error>');
            }
        } else {
            $migrations = $migrationModel->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($c = $migrations->next()) !== false) {
                $totalProcessed = 0;

                // Key is ID and not 0
                $c = reset($c);

                if ($c->isPublished()) {
                    $output->writeln('<info>'.$translator->trans('mautic.migration.trigger.triggering', array('%id%' => $c->getId())).'</info>');

                    //trigger starting action events for newly added leads
                    $output->writeln('<comment>'.$translator->trans('mautic.migration.trigger.starting').'</comment>');
                    $processed = $model->triggerStartingEvents($c, $totalProcessed, $batch, $max, $output);
                    $output->writeln(
                        '<comment>'.$translator->trans('mautic.migration.trigger.events_executed', array('%events%' => $processed)).'</comment>'."\n"
                    );

                    if ($max && $totalProcessed >= $max) {

                        continue;
                    }
                }

                $em->detach($c);
                unset($c);
            }

            unset($migrations);
        }

        unset($executionTimes['in_progress'][$command][$key]);
        file_put_contents($checkFile, json_encode($executionTimes));

        return 0;
    }
}
