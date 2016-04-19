<?php

class ModularPageExtension extends DataExtension {
	
	private static $has_one = array(
		'Modules' => 'ContentModuleArea',
	);
	
	private static $publish_with_me = array(
		'Modules'
	);
	
	private static $search_index = array(
		'Modules'
	);
	
	private static $modular_cms_fields_tab = 'Root.ContentModules';
	private static $modular_cms_fields_before = null;
	
	function updateCMSFields(FieldList $fields) {
		
		//$fields->addFieldsToTab('Root.Main', LiteralField::create('afsfsasaf', $this->owner->ModulesID), 'Content');
		
		if ($tab = $this->owner->Config()->get('modular_cms_fields_tab')) {
			$fields->addFieldsToTab($tab, $this->getModularCMSFields('Modules', 'Content Modules'), $this->owner->Config()->get('modular_cms_fields_before'));
		}
				
		return $fields;
	}
	
	function getModularCMSFields($relationName='Modules', $title='Content Modules') {
		$fields = array();
		
		$GLOBALS['_CONTENT_MODULE_PARENT_PAGEID'] = $this->owner->ID;
		
		$area = $this->owner->obj($relationName);
		
		if ($area && $area->exists()) {
			$fields[] = HeaderField::create($relationName.'Header', $title, 2);
			$fields[] = GridField::create($relationName, $title, $area->Modules(), GridFieldConfig_RecordEditor::create()
					->addComponent(new GridFieldOrderableRows('SortOrder'))
					->removeComponentsByType('GridFieldAddNewButton')
					->addComponent($add = new GridFieldAddNewMultiClass())
				);
			if (($allowed_modules = $this->owner->Config()->get('allowed_modules')) && is_array($allowed_modules) && count($allowed_modules)) {
				if (isset($allowed_modules[$relationName])) {
					$add->setClasses($allowed_modules[$relationName]);
				} else {
					$add->setClasses($allowed_modules);
				}
			} else {
				// Remove the base "ContentModule" from allowed modules.
				$classes = array_values(ClassInfo::subclassesFor('ContentModule'));
				sort($classes);
				if (($key = array_search('ContentModule', $classes)) !== false) {
					unset($classes[$key]);
				}
				$add->setClasses($classes);
			}	
		} else {
			$fields[] = LiteralField::create('SaveFirstToAddModules', '<div class="message">You must save first before you can add modules.</div>');
		}
		
		return $fields;	
	}
	
	function requireDefaultModuleRecords($relationName='Modules') {
		if (($defaults = $this->owner->Config()->get('default_modules')) && is_array($defaults) && count($defaults)) {
			if (isset($defaults[$relationName])) {
				$defaults = $defaults[$relationName];
			}
			$area = $this->owner->obj($relationName);
			if ($area && $area->exists()) {
				$modules = $area->Modules();
				if (!$modules->count()) {
					for ($i=0; $i<count($defaults); $i++) {
						if (class_exists($defaults[$i]['ClassName'])) {
							$s = Object::create($defaults[$i]['ClassName']);
							$s->ModuleAreaID = $area->ID;
							$s->SortOrder = $i;
							foreach ($defaults[$i]['Properties'] as $k => $v) {
								$s->$k = $v;
							}
							$s->write();
						}
					}
				}
			}
		}
	}
	
	// Create defaults
	function onBeforeWrite() {
		parent::onBeforeWrite();
		$has_one = $this->owner->Config()->get('has_one');
		foreach ($has_one as $k => $v) {
			if ($v == 'ContentModuleArea') {
				$idfield = $k.'ID';
				if (empty($this->owner->$idfield)) {
					$area = new $v();
					$area->write();
					$this->owner->$idfield = $area->ID;
				}
			}
		}
	}
	
	// Create defaults
	function onAfterWrite() {
		parent::onAfterWrite();
		$has_one = $this->owner->Config()->get('has_one');
		foreach ($has_one as $k => $v) {
			if ($v == 'ContentModuleArea') {
				$this->requireDefaultModuleRecords($k);
			}
		}
	}
	
	// Duplicate 
	function onBeforeDuplicate($page) {
		// Duplicate the has_one ContentModuleArea(s)
		$relations = array();
		$has_one = $this->owner->config()->get('has_one');
		foreach ($has_one as $name => $class) {
			if (is_subclass_of ($class, 	'ContentModuleArea') || $class == 'ContentModuleArea') {
				if ($item = $page->obj($name)) {
					$new = $item->duplicate();
					$field = $name.'ID';
					$page->$field = $new->ID;
				}
			}
		}
	}
	
}