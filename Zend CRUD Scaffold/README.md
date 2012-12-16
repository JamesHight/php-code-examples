Example CRUD controller
-----------------------

This is an admin CRUD controller built on a custom scaffold. See [Sit_Controller_Admin_CrudAction](https://github.com/JamesHight/php-code-examples/blob/master/Zend%20CRUD%20Scaffold/Sit/Controller/Admin/CrudAction.php) for more detail.

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
