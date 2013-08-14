<?php

namespace GroupDocs\SignatureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        //Simple index action. Renders index.html.twig template.
        return array('name' => "Signature");
    }

     /**
     * @Route("/PublicSignDocument")
     * @Method({"GET", "POST"})
     */
    public function PublicSignDocumentAction(Request $req)
    {
        //$request = Request::createFromGlobals();
        //$request = $this->get('Request');

        $postdata = file_get_contents("php://input");
        $json_post_data = json_decode($postdata, true);

        $userId = $json_post_data['userId'];
        $privateKey = $json_post_data['privateKey'];
        $documents = $json_post_data['documents'];
        $signers = $json_post_data['signers'];
        for ($i = 0; $i < count($signers); $i++) {
            $signers[$i]['placeSignatureOn'] = '';
        }
       
        require_once 'Groupdocs/groupdocs.php';
        require_once 'Groupdocs/src/GroupDocsRequestSigner.php';
        require_once 'Groupdocs/src/models/SignatureSignDocumentSettings.php';
        require_once 'Groupdocs/src/models/SignatureSignDocumentResponse.php';
        require_once 'Groupdocs/src/models/SignatureSignDocumentResult.php';

        require_once 'Groupdocs/src/SignatureApi.php';

        $signer = new \GroupDocsRequestSigner($privateKey);
        $apiClient = new \GroupDocs_ApiClient($signer);

        $signatureApi = new \SignatureApi($apiClient);

        $settings = new \SignatureSignDocumentSettings();
        $settings->documents = $documents;
        $settings->signers = $signers;
        
        $response = $signatureApi->SignDocument($userId, $settings);

        if ($userId != "") {
            $return = array("responseCode" => 200, "documentId" => $response->result->documents[0]->documentId);
        } else {
            $return = array("responseCode" => 400, "greeting" => "You have to write your name!");
        }

        $return = json_encode($return); //jscon encode the array
        return new Response($return, 200 ,array('Content-Type'=>'application/json'));//make sure it has the correct content type
    }
}
