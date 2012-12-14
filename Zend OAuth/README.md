Zend API OAuth Server Example
-----------------------------

Example OAuth RPC API controller for retrieving items by id(s)

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

Example admin CRUD controller for groups

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
