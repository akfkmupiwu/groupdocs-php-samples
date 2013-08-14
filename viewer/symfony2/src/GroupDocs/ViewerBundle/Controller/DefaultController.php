<?php

namespace GroupDocs\ViewerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        //Simple index action. Renders index.html.twig template.
        return array('name' => "Viewer");
    }
    
    /**
     * @Route("/upload")
     * @Template("GroupDocsViewerBundle:Default:viewer.html.twig")
     */
    public function uploadAction()
    {
        //Upload action. Uploads file to GroupDocs. Renders upload.html.twig template with embedded Viewer.
        /* @var Request */
        $request = $this->getRequest();

        /* @var UploadedFile */
        $uploadedFile = $request->files->get('file');
       
        // Groupdocs details
        $clientID = strip_tags(stripslashes(trim($request->get('client_id')))); //ClientId==UserId
        $apiKey = strip_tags(stripslashes(trim($request->get('private_key')))); //ApiKey==PrivateKey
        
        if (null === $uploadedFile)
            return new RedirectResponse("/web/app_dev.php/groupdocs/");

        /* @var string */
        $filename = $uploadedFile->getPathname(); //temp name of the file
        $name = $uploadedFile->getClientOriginalName(); //original name of the file
        require_once 'Groupdocs/groupdocs.php';
        require_once 'Groupdocs/src/GroupDocsRequestSigner.php';
        require_once 'Groupdocs/src/StorageAPI.php';
        require_once 'Groupdocs/src/FileStream.php';
        require_once 'Groupdocs/src/models/UploadResponse.php';
        require_once 'Groupdocs/src/models/UploadRequestResult.php';
        $fs = \FileStream::fromFile($filename);
        $signer = new \GroupDocsRequestSigner($apiKey);
        $apiClient = new \GroupDocs_ApiClient($signer); // new way to create apiClient - PHP SDK 1.1
        $apiStorage = new \StorageAPI($apiClient);
        $result = $apiStorage->Upload($clientID, $name, 'uploaded', "", $fs);
        return array('name' => $name, 'guid' => $result->result->guid);
    }
}