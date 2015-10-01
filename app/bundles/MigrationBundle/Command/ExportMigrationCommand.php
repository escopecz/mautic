<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Command;

use Mautic\MigrationBundle\Model\MigrationModel;
use Mautic\MigrationBundle\Entity\Migration;
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
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of entities to process per round. Defaults to 10000.', 10000)
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
            $this->processMigration($migration, $migrationModel, $batch, $output, $translator);
        } else {
            $migrations = $migrationModel->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($migration = $migrations->next()) !== false) {
                $totalProcessed = 0;

                // Key is ID and not 0
                $migration = reset($migration);

                $this->processMigration($migration, $migrationModel, $batch, $output, $translator);

                $em->detach($migration);
                unset($migration);
            }

            unset($migrations);
        }

        unset($executionTimes['in_progress'][$command][$key]);
        file_put_contents($checkFile, json_encode($executionTimes));

        return 0;
    }

    /**
     *
     */
    protected function processMigration(Migration $migration, MigrationModel $migrationModel, $batch, OutputInterface $output, $translator)
    {
        if ($migration !== null && $migration->isPublished()) {
            $output->writeln('<info>'.$translator->trans('mautic.migration.export.starting', array('%id%' => $migration->getId())).'</info>');
            $blueprint = $migrationModel->triggerExport($migration, $batch, $output);
            $output->writeln('<info>'.$translator->trans('mautic.migration.export.progress', array(
                '%processedEntities%' => $blueprint['processedEntities'],
                '%totalEntities%' => $blueprint['totalEntities']
            )).'</info>');
        } else {
            $output->writeln('<error>'.$translator->trans('mautic.migration.template.not_found', array('%id%' => $migration->getId())).'</error>');
        }
    }
}
