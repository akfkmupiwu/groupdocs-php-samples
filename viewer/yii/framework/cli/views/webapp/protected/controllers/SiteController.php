<?php

class SiteController extends Controller {

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex($res=null) {

        $model = new GroupdocsForm;
        // default values
        $height = '900px';
        $width = '100%';
        $iframe = '';
        
        // form sent
        if (!empty($_POST)) {
            
            $model->attributes = $_POST['GroupdocsForm'];
            
            // Groupdocs details
            $clientID = strip_tags(stripslashes(trim($model->client_id)));
            $apiKey = strip_tags(stripslashes(trim($model->api_key)));
            
            // file doc manipulation
            if (!empty($_FILES["GroupdocsForm"]["name"]["file"])) {
				// now uploaded file has priority
				$model->file_id = '';
                //
                $uploads_dir = dirname(__FILE__) . '/../../images';
                $tmp_name = $_FILES["GroupdocsForm"]["tmp_name"]["file"];
                $name = $_FILES["GroupdocsForm"]["name"]["file"];
                move_uploaded_file($tmp_name, "$uploads_dir/$name");

                //  GroupDocs SDK
                Yii::import('application.vendors.groupdocs-php.APIClient', true);          
                Yii::import('application.vendors.groupdocs-php.StorageAPI');
                Yii::import('application.vendors.groupdocs-php.GroupDocsRequestSigner');
                Yii::import('application.vendors.groupdocs-php.models.StorageStorageInputFoldersInput');
                Yii::import('application.vendors.groupdocs-php.models.UploadResponse');
                Yii::import('application.vendors.groupdocs-php.models.UploadRequestResult');
                // groupdocs api
                
                $signer = new GroupDocsRequestSigner($apiKey);
                //$apiClient = new APIClient($apiKey, "https://api.groupdocs.com/v2.0"); //old api - SDK v1.0
                $apiClient = new APIClient($signer); // PHP SDK V1.1
                $api = new StorageAPI($apiClient);
                $result = $api->Upload($clientID, $name, 'uploaded', "file://$uploads_dir/$name");
                unlink("$uploads_dir/$name");
            }

			///// Show iframe

            // insert groupdocs File ID
            if ($model->file_id) {
                $iframe = '<iframe src="https://apps.groupdocs.com/document-viewer/embed/' . $model->file_id . '" frameborder="0" width="' . $width . '" height="' . $height . '"></iframe>';
            } 
			// upload file
			if (isset($result->result->guid) && $result->result->guid) {
                $iframe = '<iframe src="https://apps.groupdocs.com/document-viewer/embed/' . @$result->result->guid . '" frameborder="0" width="' . $width . '" height="' . $height . '"></iframe>';
            }
        }
        // Render page
        $this->render('index', array('iframe' => $iframe,'model'=>$model,'res'=>$res));
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the contact page
     */
    public function actionContact() {
        $model = new ContactForm;
        if (isset($_POST['ContactForm'])) {
            $model->attributes = $_POST['ContactForm'];
            if ($model->validate()) {
                $headers = "From: {$model->email}\r\nReply-To: {$model->email}";
                mail(Yii::app()->params['adminEmail'], $model->subject, $model->body, $headers);
                Yii::app()->user->setFlash('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');
                $this->refresh();
            }
        }
        $this->render('contact', array('model' => $model));
    }

    /**
     * Displays the login page
     */
    public function actionLogin() {
        $model = new LoginForm;

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && $model->login())
                $this->redirect(Yii::app()->user->returnUrl);
        }
        // display the login form
        $this->render('login', array('model' => $model));
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout() {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }

    /**
     * Declares class-based actions.
     */
    public function actions() {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
            ),
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }

}