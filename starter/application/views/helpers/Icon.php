<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Local_View_Helper_Icon extends Zend_View_Helper_Abstract
{

	public $user_visible = 'user-silhouette-checked.png';
	public $user_hidden = 'user-silhouette-question.png';
	public $user_editable = 'user-business.png';
	public $user_noteditable = 'user-worker-boss.png';
	public $user_canchat = 'user-chat.png';
	public $user_cannotchat = 'user-nochat.png';
	
	public function icon(){
		return $this;
	}
	
	public function getPath(){
		return Zend_Registry::get('baseUrl') . 'public/images/icons/';
	}

}