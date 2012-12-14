Zend OAuth API Server
=====================

Example OAuth RPC API controller for retrieving items by id(s)
--------------------------------------------------------------

This is an OAuth protected API controller. See [Sit_Controller_UserAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/UserAction.php) and [Sit_Controller_BaseAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/BaseAction.php) for more detail.

	class ItemController extends Sit_Controller_UserAction {

		public function getAction() {
			$id = $this->_getParam('id');
			$ids = $this->_getParam('ids');
			// admin flag
			$admin = $this->_getBool('admin');
			
			// Get id
			if ($id) {
				$data = Core_Model_Mongo_Item::getDataById($id, $this->user, $admin);
			}
			// Get multiple ids
			else if ($ids) {
				$ids = Sit_Json::decode($ids);
				$data = Core_Model_Mongo_Item::getDataByIds($ids, $this->user, $admin);
			}
			else {
				// Missing parameter
				throw new Sit_Exception_Api('You must provide one of the following fields: id or ids', 
							Sit_Errors::PARAMETER);
			}

			// no data found return null
			if (!$data) {
				$data = null;
			}

			// Send JSON response
			$this->response($data);
		}
		
		public function saveAction() {
			$data = $this->_getRequiredParam('data');
			$data = Sit_Json::decode($data);
			// admin flag
			$admin = $this->_getBool('admin');
					
			// Item already exists in db?
			if (isset($data['id'])) {
				$model = false;

				// Check for third party prefix on id
				// Don't allow anyone to update third party items
				if (substr($data['id'], 0, 2) != 'fs')
					$model = Core_Model_Mongo_Item::getById($data['id'], $this->user);

				if (!$model) {
					throw new Sit_Exception_Api('You do not have permission to to save that item.', 
								Sit_Errors::PERMISSION_DENIED);
				}
			}
			else {
				// Create new item

				$model = new Core_Model_Mongo_Item();
				// set item ownership
				$model->user_id = $this->user->id;
				$model->site_id = $this->application->site_id;
				// Set data before save to trigger validation
				$model->setData($data, $admin);
				// save item to get id
				$model->save();
				// assign id to internal index
				$data['id'] = $model->_id;

				// code below will update and save id change
			}
			
			// set and validate data
			$model->setData($data, $admin);
			// save changes
			$model->save();
			
			// return id to client
			$this->response(array('id'=>$data['id']));
		}
	}

Example admin CRUD controller
-----------------------------

This is an admin CRUD controller built on a custom scaffold. See [Sit_Controller_Admin_CrudAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/Admin/CrudAction.php) for more detail.

	class Admin_GroupController extends Sit_Controller_Admin_CrudAction {

		// Class name of model
		protected $crudClass = 'Core_Model_Group';
		// Fields to display on the index page
		protected $crudIndexFields = array(	array('type'=>Sit_Controller_Admin_CrudAction::TYPE_FIELD,
												'field'=>'id',
												'label'=>'Id'),
											array('type'=>Sit_Controller_Admin_CrudAction::TYPE_FIELD,
												'field'=>'name',
												'label'=>'Name'),
											array('type'=>Sit_Controller_Admin_CrudAction::TYPE_EDIT,
												'label'=>'Edit'));

		// Fields we should use when performing a text search
		protected $crudSearchFields = array('name');
		
		// override the delete function to disable it
		public function  deleteAction() {}

	}

Associated Zend form

	<?php
	/**
	 * Standard Zend_Form with custom decorators and some helper functions
	 **/	 

	class Admin_Form_Group extends Sit_Form {
		
		public function start() {
			$this->addElement('hidden', 'id');
			
			$this->addElement('text', 'name', array(
					'label' => 'Name',
					'required' => true,
					'validators' => array('NotEmpty')
				));

			// Permissions
			$options = array();
			foreach (Doctrine::getTable('Core_Model_Permission')->findAll() as $option) {
				$options[$option->id] = $option->label ;
			}
			$this->addElement('multiselect', 'permission_ids', array(
					'label' => 'Permissions',
					'multioptions' => $options
				));

			$submit = $this->addElement('submit', 'submit', array(
					'label' => 'Create',
					'order' => 1000
				));
	    }

	    /**
	     * Load a Group object into the form
	     **/

	    public function loadClass($group) {
	    	$this->getElement('name')->setValue($group->name);

			$permissionIds = array();
			foreach ($group->Permissions as $permission) {
				$permissionIds[] = $permission->id;
			}
			$this->getElement('permission_ids')->setValue($permissionIds);
		}
	    
	    /**
	     * Save the contents of the form to a Group object
	     **/

	    public function updateClass($group) {
	    	$group->name = $this->getElement('name')->getValue();

			if (!$group->id) {
				$group->save();
			}

			// Permissions
			$ids = $this->getElement('permission_ids')->getValue();
			// Custom helper function for updating many to many associates
			$this->updateManyToMany($group, $ids, 'Core_Model_GroupPermission', 'group_id', 'permission_id');
			// Refresh cached associated permissions
			$group->refreshRelated('Permissions');

			// save changes
	    	$group->save();
	    }

	}
