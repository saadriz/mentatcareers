<?php
/**
 * Created by PhpStorm.
 * User: Marwen
 * Date: 03/04/2016
 * Time: 10:49
 */

namespace AppBundle\Command;

use AppBundle\Entity\Profile;
use Doctrine\ORM\EntityManager;
use Goutte\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

class ImportFromIndeedCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('scrapper:indeed:import')->setDefinition(
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
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client();
        $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false,),));
        $client->setClient($guzzleClient);
        // authentication
//        $crawler = $client->request('GET', 'https://secure.indeed.com/account/login');
//        $form = $crawler->filterXPath('//*[@id="loginform"]')->form();
//        $crawler = $client->submit($form, array('email' => 'marouen.chrif@gmail.com', 'password' => '****',
//            'remember' => '1'));

//        while (true) {
        try {
            $this->logger->addInfo('Starting the scrapper command.');
            $profiles = [];
            $searchKeys = [
                'gmail', 'e-mail', 'javascript', 'engineering', 'software', 'electrical', 'resume', 'email', 'mail', 'contact',
                'com', 'yahoo', '@gmail.com', ''
            ];
            foreach ($searchKeys as $searchKey) {
                var_dump("Start searching with the Key : " . $searchKey);
                $baseUrl = 'http://www.indeed.com/resumes';
                if (strpos($searchKey, '-') >= 0 || strpos($searchKey, '.') >= 0) {
                    $baseUrl .= '?q=' . $searchKey /*. '&co=US'*/;
                } else {
                    $baseUrl .= '/' . $searchKey /*. '?&co=US'*/;
                }
                $start = 0;
                $hasMorePage = true;
                while ($hasMorePage) {
                    $tmp = [];
                    $url = $baseUrl . "&start=$start";
                    $url = ltrim($url, "http://");
                    $crawler = $client->request('GET', 'http://' . $url);
                    $tmp = $crawler->filter('.app_link')->each(function ($node) {
                        $url = $node->attr('href');
                        $name = $node->text();
                        return ["name" => $name, "link" => $url];
                    });
                    if (count($tmp) > 0) {
                        $profiles = array_merge($profiles, $tmp);
                        $start += 50;
                    } else {
                        $hasMorePage = false;
                    }
                };
                $this->logger->addInfo('Current profiles count => ' . $searchKey . ' : '  . count($profiles));
		sleep(60);
            }
            $this->logger->addInfo(count($profiles) . ' profile ');
            $progress = new ProgressBar($output, count($profiles));
            foreach ($profiles as $_profile) {
                var_dump($_profile['name']);
                $crawler = $client->request('GET', 'http://www.indeed.com' . $_profile['link']);
                if (!is_null($crawler)) {
                    $content = $crawler->filter('#resume_body')->each(function ($node) {
                        $content = $node->html();
                        $emails = extract_email_address($content);
                        return ['email' => $emails, 'content' => $content];
                    });
                    $profile = null;
                    $existing = $this->em->getRepository('AppBundle:Profile')->findOneBy(['name' => $_profile['name']]);
                    if (count($content) > 0) {
                        $content = $content[0];
                        $email = $content['email'];
                        if (count($email) === 0) {
                            $email = extract_email_address($_profile['name']);
                        } else {
                            $email = array_merge($email, extract_email_address($_profile['name'], $email));
                        }
                        if (is_null($existing)) {
                            $resumeScrapped = $content["content"];
                            $profile = new Profile();
                            $profile->setName($_profile['name'])
                                ->setEmail($email)
                                ->setResumeScrapped($resumeScrapped);
                            $this->em->persist($profile);
                        } else {
                            if (count($email) > 0) {
                                $existing->setEmail($email);
                            }
                        }
                    } else {
                        $email = extract_email_address($_profile['name']);
                        if (is_null($existing)) {
                            $profile = new Profile();
                            $profile->setName($_profile['name'])
                                ->setEmail($email)
                                ->setResumeScrapped('');
                            $this->em->persist($profile);
                        } else {
                            if (count($email) > 0) {
                                $existing->setEmail($email);
                            }
                        }
                    }
                    $this->em->flush();
                    if(!is_null($profile)) {
                        $this->em->clear($profile);
                    }
                    $progress->advance();
                    $this->logger->addInfo('Profile imported.');
 		    sleep(10);
                }
            }
            $progress->finish();
            $this->logger->addInfo('Scrapper command finished with success.');
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            $output->writeln($e->getMessage());
        }
//        }
    }
}

function extract_email_address($string, $emails = [])
{
    $string = strtolower($string);

    $string = str_replace(' (at) ', '@', $string);
    $string = str_replace(' (dot) ', '.', $string);
    $string = str_replace(' gmail ', 'gmail', $string);

    $string = str_replace('@ gmail.com', '@gmail.com ', $string);
    $string = str_replace(' gmail dot com', 'gmail.com ', $string);
    $string = str_replace(' @ gmail dot com', '@gmail.com ', $string);
    $string = str_replace('   at gmail  dot com', '@gmail.com ', $string);
    $string = str_replace(' [@] GMAIL [D0T] [C0M]', '@gmail.com ', $string);

    $string = str_replace(' AT  gmail  DOT com', '@gmail.com ', $string);
    $string = str_replace('@Gmail ', '@gmail.com ', $string);
    $string = str_replace('@gmail ', '@gmail.com ', $string);

    $string = str_replace(',com', '.com ', $string);
    $string = str_replace(' com', '.com ', $string);
    $string = str_replace(' org', '.org ', $string);

    $string = str_replace(' gmail', '@gmail', $string);
    $string = str_replace(' yahoo', '@yahoo', $string);
    $string = str_replace(' hotmail', '@hotmail', $string);

    $string = str_replace('<', ' ', $string);
    $string = str_replace('>', ' ', $string);
    $string = str_replace('(', ' ', $string);
    $string = str_replace(')', ' ', $string);


    $string = str_replace(' @gmail', '@gmail', $string);
    foreach (preg_split('/\s/', $string) as $token) {
        $email = filter_var(filter_var($token, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);
        if ($email !== false && !in_array(strtolower($email), $emails)) {
            $email = str_replace('class=data_display', '', $email);
            $email = str_replace('rel=nofollowhttp', '', $email);
            $email = str_replace('class=bold', '', $email);
            $email = str_replace('class=work_description', '', $email);
            $email = str_replace('itemprop=jobtitle', '', $email);
            $email = str_replace('smtp.mail=', '', $email);

            $emails[] = strtolower($email);
        }
    }

    return $emails;
}

