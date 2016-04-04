<?php
/**
 * Created by PhpStorm.
 * User: Marwen
 * Date: 04/04/2016
 * Time: 20:28
 */

namespace AppBundle\Command;


use AppBundle\Entity\Profile;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteDuplicatedEmailsCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('mentatcareers:delete:duplicated_email')->setDefinition(
            []
        )->setDescription('Import all profiles.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profiles = $this->em->getRepository('AppBundle:Profile')->retrieveProfilesWithNotEmptyEmail();
        $progress = new ProgressBar($output, count($profiles));
        foreach ($profiles as $key => $profile) {
            /**
             * @var $profile Profile
             */
            $profile->setEmail(array_unique($profile->getEmail()));
            $this->em->flush();
            $this->em->clear($profile);
            $progress->advance();
        }
        $progress->finish();
    }

}