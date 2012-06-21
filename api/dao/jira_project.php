<?php
class DAO_JiraProject extends C4_ORMHelper {
	const ID = 'id';
	const JIRA_ID = 'jira_id';
	const JIRA_KEY = 'jira_key';
	const NAME = 'name';
	const URL = 'url';
	const ISSUETYPES_JSON = 'issuetypes_json';
	const STATUSES_JSON = 'statuses_json';
	const VERSIONS_JSON = 'versions_json';
	const LAST_SYNCED_AT = 'last_synced_at';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO jira_project () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'jira_project', $fields);
		
		// Log the context update
	    //DevblocksPlatform::markContextChanged('example.context', $ids);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('jira_project', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_JiraProject[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, jira_id, jira_key, name, url, issuetypes_json, statuses_json, versions_json, last_synced_at ".
			"FROM jira_project ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_JiraProject	 
	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param unknown_type $remote_id
	 * @return Model_JiraProject|null
	 */
	static function getByJiraId($remote_id) {
		// [TODO] Cache!!
		$projects = self::getWhere();
		
		foreach($projects as $project_id => $project) { /* @var $project Model_JiraProject */
			if($project->jira_id == $remote_id)
				return $project;
		}
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_JiraProject[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_JiraProject();
			$object->id = $row['id'];
			$object->jira_id = $row['jira_id'];
			$object->jira_key = $row['jira_key'];
			$object->name = $row['name'];
			$object->url = $row['url'];
			$object->last_synced_at = $row['last_synced_at'];
			
			if(false !== (@$obj = json_decode($row['issuetypes_json'], true))) {
				$object->issue_types = $obj;
			}
			
			if(false !== (@$obj = json_decode($row['statuses_json'], true))) {
				$object->statuses = $obj;
			}
			
			if(false !== (@$obj = json_decode($row['versions_json'], true))) {
				$object->versions = $obj;
			}

			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM jira_project WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => 'cerberusweb.contexts.',
                	'context_ids' => $ids
                )
            )
	    );
	    */
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_JiraProject::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"jira_project.id as %s, ".
			"jira_project.jira_id as %s, ".
			"jira_project.jira_key as %s, ".
			"jira_project.name as %s, ".
			"jira_project.url as %s, ".
			"jira_project.issuetypes_json as %s, ".
			"jira_project.statuses_json as %s, ".
			"jira_project.versions_json as %s, ".
			"jira_project.last_synced_at as %s ",
				SearchFields_JiraProject::ID,
				SearchFields_JiraProject::JIRA_ID,
				SearchFields_JiraProject::JIRA_KEY,
				SearchFields_JiraProject::NAME,
				SearchFields_JiraProject::URL,
				SearchFields_JiraProject::ISSUETYPES_JSON,
				SearchFields_JiraProject::STATUSES_JSON,
				SearchFields_JiraProject::VERSIONS_JSON,
				SearchFields_JiraProject::LAST_SYNCED_AT
			);
			
		$join_sql = "FROM jira_project ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'jira_project.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		array_walk_recursive(
			$params,
			array('DAO_JiraProject', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
	
		return array(
			'primary_table' => 'jira_project',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			*/
		}
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY jira_project.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_JiraProject::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT jira_project.id) " : "SELECT COUNT(jira_project.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_JiraProject implements IDevblocksSearchFields {
	const ID = 'j_id';
	const JIRA_ID = 'j_jira_id';
	const JIRA_KEY = 'j_jira_key';
	const NAME = 'j_name';
	const URL = 'j_url';
	const ISSUETYPES_JSON = 'j_issuetypes_json';
	const STATUSES_JSON = 'j_statuses_json';
	const VERSIONS_JSON = 'j_versions_json';
	const LAST_SYNCED_AT = 'j_last_synced_at';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'jira_project', 'id', $translate->_('common.id'), null),
			self::JIRA_ID => new DevblocksSearchField(self::JIRA_ID, 'jira_project', 'jira_id', $translate->_('dao.jira_project.jira_id'), null),
			self::JIRA_KEY => new DevblocksSearchField(self::JIRA_KEY, 'jira_project', 'jira_key', $translate->_('dao.jira_project.jira_key'), Model_CustomField::TYPE_SINGLE_LINE),
			self::NAME => new DevblocksSearchField(self::NAME, 'jira_project', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::URL => new DevblocksSearchField(self::URL, 'jira_project', 'url', $translate->_('common.url'), Model_CustomField::TYPE_URL),
			self::ISSUETYPES_JSON => new DevblocksSearchField(self::ISSUETYPES_JSON, 'jira_project', 'issuetypes_json', $translate->_('dao.jira_project.issuetypes_json'), null),
			self::STATUSES_JSON => new DevblocksSearchField(self::STATUSES_JSON, 'jira_project', 'statuses_json', $translate->_('dao.jira_project.statuses_json'), null),
			self::VERSIONS_JSON => new DevblocksSearchField(self::VERSIONS_JSON, 'jira_project', 'versions_json', $translate->_('dao.jira_project.versions_json'), null),
			self::LAST_SYNCED_AT => new DevblocksSearchField(self::LAST_SYNCED_AT, 'jira_project', 'last_synced_at', $translate->_('dao.jira_project.last_synced_at'), Model_CustomField::TYPE_DATE),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		//}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_JiraProject {
	public $id;
	public $jira_id;
	public $jira_key;
	public $name;
	public $url;
	public $issue_types = array();
	public $statuses = array();
	public $versions = array();
	public $last_synced_at;
};

class View_JiraProject extends C4_AbstractView {
	const DEFAULT_ID = 'jira_projects';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('JIRA Projects');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_JiraProject::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_JiraProject::NAME,
			SearchFields_JiraProject::JIRA_KEY,
			SearchFields_JiraProject::URL,
			SearchFields_JiraProject::LAST_SYNCED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_JiraProject::ID,
			SearchFields_JiraProject::ISSUETYPES_JSON,
			SearchFields_JiraProject::JIRA_ID,
			SearchFields_JiraProject::STATUSES_JSON,
			SearchFields_JiraProject::VERSIONS_JSON,
		));
		
		$this->addParamsHidden(array(
			SearchFields_JiraProject::ID,
			SearchFields_JiraProject::JIRA_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_JiraProject::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_JiraProject', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_JiraProject', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:wgm.jira::project/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
			case SearchFields_JiraProject::ID:
			case SearchFields_JiraProject::JIRA_ID:
			case SearchFields_JiraProject::JIRA_KEY:
			case SearchFields_JiraProject::NAME:
			case SearchFields_JiraProject::URL:
			case SearchFields_JiraProject::LAST_SYNCED_AT:
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
		}
	}

	function getFields() {
		return SearchFields_JiraProject::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_JiraProject::ID:
			case SearchFields_JiraProject::JIRA_ID:
			case SearchFields_JiraProject::JIRA_KEY:
			case SearchFields_JiraProject::NAME:
			case SearchFields_JiraProject::URL:
			case SearchFields_JiraProject::LAST_SYNCED_AT:
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_JiraProject::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_JiraProject::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_JiraProject::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_JiraProject::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_JiraProject::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_JiraProject extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.jira.project';
	
	function getRandom() {
		//return DAO_JiraProject::random();
	}
	
	function getMeta($context_id) {
		$project = DAO_JiraProject::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		//$friendly = DevblocksPlatform::strToPermalink($example->name);
		
		return array(
			'id' => $project->id,
			'name' => $project->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&=type=jira_project&id=%d",$context_id), true),
		);
	}
	
	function getContext($project, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'JIRA Project:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_JiraProject::ID);

		// Polymorph
		if(is_numeric($project)) {
			$project = DAO_JiraProject::get($project);
		} elseif($project instanceof Model_JiraProject) {
			// It's what we want already.
		} else {
			$project = null;
		}
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_JiraProject::ID;
		
		if($project) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $project->name;
			$token_values['id'] = $project->id;
			
			// URL
			//$url_writer = DevblocksPlatform::getUrlService();
			//$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=example.object&id=%d-%s",$project->id, DevblocksPlatform::strToPermalink($project->name)), true);
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_JiraProject::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}	
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->view_columns = array(
			SearchFields_JiraProject::NAME,
			SearchFields_JiraProject::JIRA_KEY,
			SearchFields_JiraProject::URL,
			SearchFields_JiraProject::LAST_SYNCED_AT,
		);
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_JiraProject::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = false;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_JiraProject::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_JiraProject::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};