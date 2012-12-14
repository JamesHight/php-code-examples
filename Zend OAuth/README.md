Zend API OAuth Server Example
=============================

Example OAuth RPC API controller for retrieving items by id(s)
--------------------------------------------------------------

This is an OAuth protected API controller. See [Sit_Controller_UserAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/UserAction.php) for more detail.

	class ItemController extends Sit_Controller_UserAction {

		public function getAction() {
			$id = $this->_getParam('id');
			$ids = $this->_getParam('ids');
			// admin flag
			$admin = $this->_getBool('admin');
			
			if ($id) {
				$data = Core_Model_Mongo_Item::getDataById($id, $this->user, $admin);
			}
			else if ($ids) {
				$ids = Sit_Json::decode($ids);
				$data = Core_Model_Mongo_Item::getDataByIds($ids, $this->user, $admin);
			}
			else {
				throw new Sit_Exception_Api('You must provide one of the following fields: id or ids', 
							Sit_Errors::PARAMETER);
			}

			if (!$data) {
				$data = null;
			}

			$this->response($data);
		}
		
		public function saveAction() {
			$data = $this->_getRequiredParam('data');
			$data = Sit_Json::decode($data);
			$admin = $this->_getBool('admin');
					
			if (isset($data['id'])) {
				$model = false;
				if (substr($data['id'], 0, 2) != 'fs')
					$model = Core_Model_Mongo_Item::getById($data['id'], $this->user);
				
				if (!$model) {
					throw new Sit_Exception_Api('You do not have permission to to save that item.', 
								Sit_Errors::PERMISSION_DENIED);
				}
			}
			else {
				$model = new Core_Model_Mongo_Item();
				$model->user_id = $this->user->id;
				$model->site_id = $this->application->site_id;
				$model->setData($data, $admin); // Set data before save to validate it
				$model->save();
				$data['id'] = $model->_id;
			}
			
			$model->setData($data, $admin);
			$model->save();
			
			$this->response(array('id'=>$data['id']));
		}
	}

Example admin CRUD controller
-----------------------------

This is an admin CRUD controller build on a custom scaffold. See [Sit_Controller_Admin_CrudAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20OAuth/Sit/Controller/Admin/CrudAction.php) for more detail.

	class Admin_GroupController extends Sit_Controller_Admin_CrudAction {

		protected $crudClass = 'Core_Model_Group';
		protected $crudIndexFields = array(	array('type'=>Sit_Controller_Admin_CrudAction::TYPE_FIELD,
												'field'=>'id',
												'label'=>'Id'),
											array('type'=>Sit_Controller_Admin_CrudAction::TYPE_FIELD,
												'field'=>'name',
												'label'=>'Name'),
											array('type'=>Sit_Controller_Admin_CrudAction::TYPE_EDIT,
												'label'=>'Edit'));

		protected $crudSearchFields = array('name');
		
		// override delete function to disable
		public function  deleteAction() {
			
		}

	}

Associate form

	<?php
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

	    
	    public function loadClass($group) {
	    	$this->getElement('name')->setValue($group->name);

			$permissionIds = array();
			foreach ($group->Permissions as $permission) {
				$permissionIds[] = $permission->id;
			}
			$this->getElement('permission_ids')->setValue($permissionIds);
		}
	    
	    public function updateClass($group) {
	    	$group->name = $this->getElement('name')->getValue();

			if (!$group->id) {
				$group->save();
			}

			// Permissions
			$ids = $this->getElement('permission_ids')->getValue();
			$this->updateManyToMany($group, $ids, 'Core_Model_GroupPermission', 'group_id', 'permission_id');
			$group->refreshRelated('Permissions');

	    	$group->save();

	    }

	}
