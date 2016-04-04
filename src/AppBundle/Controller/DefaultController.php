<?php

namespace AppBundle\Controller;

use Goutte\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $response = null;
        $profiles = $this->getDoctrine()->getEntityManager()->getRepository('AppBundle:Profile')
        ->retrieveProfilesWithNotEmptyEmail();

        if ($request->getMethod() === "GET") {
            $response = $this->render('default/index.html.twig', ['profiles' => $profiles]);
        }

        return $response;
    }

    /**
     * @Route("/download_mails", name="download_mails")
     */
    public function generateCsvAction(){

        $response = new StreamedResponse();
        $response->setCallback(function(){

            $handle = fopen('php://output', 'w+');

            // Add the header of the CSV file
            fputcsv($handle, array('Name', 'email'),';');
            // Query data from database
            $results = $this->getDoctrine()->getConnection()->query( "SELECT * FROM profile p where p.email != 'a:0:{}'" );
            // Add the data queried from database
            while( $row = $results->fetch() )
            {
                fputcsv(
                    $handle, // The file pointer
                    array($row['name'], implode(' , ', unserialize($row['email']))), // The fields
                    ';' // The delimiter
                );
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="export.csv"');

        return $response;
    }
}
