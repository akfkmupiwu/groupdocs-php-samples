<?php

/**
 * ContactForm class.
 * ContactForm is the data structure for keeping
 * contact form data. It is used by the 'contact' action of 'SiteController'.
 */
class GroupdocsForm extends CFormModel
{
	public $client_id;
	public $api_key;
	public $file_id;
	public $file;
        public $id;
        
        
	/**
	 * Returns the static model of the specified AR class.
	 * @return Advert the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			array('client_id,api_key,file_id,file', 'safe', 'on'=>''),
		);
	}

        
	/**
	 * Declares customized attribute labels.
	 * If not declared here, an attribute would have a label that is
	 * the same as its name with the first letter in upper case.
	 */
	public function attributeLabels()
	{
		return array(
			'client_id'=>'Client ID',
			'api_key'=>'Api Key',
			'file_id'=>'File ID',
			'file'=>'Local File',
		);
	}
}