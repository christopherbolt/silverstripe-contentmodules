<?php

class ContentModule extends DataObject {
	
	//private static $singular_name = 'Content Module';
	//private static $plural_name = 'Content Modules';
	
	private static $default_sort = 'SortOrder ASC';
		
	private static $db = array(
		'SortOrder' => 'Int',
		'URLSegment' => 'Varchar'
	);
	
	private static $has_one = array(
		'ModuleArea' => 'ContentModuleArea',
	);
		
	// Summary fields
  	private static $summary_fields = array(
    	'TitleForGridfield',
		'TypeLabel',
		'contentPreview'
  	);
	
	private static $field_labels = array(
		'TitleForGridfield' => 'Title',
		'TypeLabel' => 'Type',
		'contentPreview' => 'Preview'
	);
	
	private static $search_index = array(
	);
	
	private static $extensions = array(
		'DefaultCan',
		'PublishWithMe',
    	"Versioned('Stage', 'Live')",
	);
	
	private static $parent_relation = 'ModuleArea';
	
	function Page() {
		return Controller::curr();
	}
	
	function TitleForGridfield() {
		if (isset($this->Title)) {
			return $this->Title;	
		} else if (isset($this->Name)) {
			return $this->Name;
		} else {
			return '(No title)';	
		}
	}
	
	function contentPreview() {
		$content = '';
		if (isset($this->Content)) {
			$content = $this->Content;	
		} else if (method_exists($this, 'Content')) {
			$content = $this->Content();	
		}
		return DBField::create_field('Text',$content,'Content')->ContextSummary(100);
	}
	
	function TypeLabel() {
		return $this->config()->get('singular_name') ? $this->config()->get('singular_name') : $this->ClassName;
	}
	
	function TemplateNames() {
		$parentClasses = class_parents($this->ClassName);
		$templates = array();
		$templates[] = $this->ClassName;
		foreach($parentClasses as $className) {
			if (	$className == 'DataObject') break;
			$templates[] = $className;
		}
		return $templates;
	}
	
	function forTemplate() {
		$templates = $this->TemplateNames();
		return $this->renderWith($templates);	
	}
	
	function forLoop($pos=0) {
		$templates = $this->TemplateNames();
		return $this->customise(array(
			'LoopPos' => $pos
		))->renderWith($templates);	
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->SortOrder && $this->ModuleAreaID && ($modules = ContentModule::get()->filter(array('ModuleAreaID' => $this->ModuleAreaID)))) {
            $this->SortOrder = $modules->max('SortOrder') + 1;
        }
	}
	
	// This is just for if you arrive directly at this base class, perhaps by choosing the 'New Record' button if using BetterButtons.
	// Displays a class chooser
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('ModuleAreaID');
		$fields->removeByName('SortOrder');
		$fields->removeByName('URLSegment');
		
		if ($this->ClassName == 'ContentModule') {
			$fields->addFieldToTab('Root.Main', DropDownField::create('ClassName', 'Choose Module Type', $this->getClassesForDropdown()));
			
			$fields->addFieldToTab('Root.Main', LiteralField::create('ClassNameMessage', '<div class="message">You can add content after saving for the first time.</div>'));
			
			Config::inst()->update('ContentModule', 'better_buttons_enabled', false);
		} else {
			$fields->addFieldToTab('Root.Settings', DropDownField::create('ClassName', 'Module Type', $this->getClassesForDropdown()));	
		}
		
		return $fields;	
	}
	
	public function getClassesForDropdown() {
		$result = array();
		
		if (
			isset($GLOBALS['_CONTENT_MODULE_PARENT_PAGEID']) && 
			($page = Page::get()->byId($GLOBALS['_CONTENT_MODULE_PARENT_PAGEID'])) &&
			($allowed_modules = $page->Config()->get('allowed_modules')) && 
			is_array($allowed_modules) && count($allowed_modules)
			) {
				
			$controller = Controller::curr();
			
			$relationName = $controller->request->param('FieldName');
						
			if (isset($allowed_modules[$relationName])) {
				$classes = $allowed_modules[$relationName];
			} else {
				$classes = $allowed_modules;
			}
			
		} else {
			$classes = array_values(ClassInfo::subclassesFor('ContentModule'));
			sort($classes);
		}

		$kill_ancestors = array('ContentModule' => true);
		foreach($classes as $class => $title) {
			if(!is_string($class)) {
				$class = $title;
				$is_abstract = (($reflection = new ReflectionClass($class)) && $reflection->isAbstract());
				if (!$is_abstract) {
					$title = singleton($class)->i18n_singular_name();
				}
			} else {
				$is_abstract = (($reflection = new ReflectionClass($class)) && $reflection->isAbstract());
			}

			if ($ancestor_to_hide = Config::inst()->get($class, 'hide_ancestor', Config::FIRST_SET)) {
				$kill_ancestors[$ancestor_to_hide] = true;
			}

			if($is_abstract || !singleton($class)->canCreate()) {
				continue;
			}

			$result[$class] = $title;
		}

		if($kill_ancestors) {
			foreach($kill_ancestors as $class => $bool) {
				unset($result[$class]);
			}
		}

		return $result;
	}
	
} 


?>