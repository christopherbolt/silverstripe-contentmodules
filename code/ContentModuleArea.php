<?php

class ContentModuleArea extends DataObject {
	
	private static $has_many = array(
		'Modules' => 'ContentModule',
	);
	
	private static $search_index = array(
		'Modules'
	);
	
	private static $publish_with_me = array(
		'Modules'
	);
	
	private static $extensions = array(
		'DefaultCan',
		'PublishWithMe',
    	"Versioned('Stage', 'Live')",
	);
	
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
	
	// Duplicate 
	function onAfterDuplicate($page) {
		if ($this->owner->ID < 1) {
			// Can only add relations if this exists.
			return;
		}
		// Has many relations to duplicate...
		$relations = array('Modules');
		$this->owner->_inDuplication = true;
		foreach ($relations as $relation) {
			foreach($page->$relation() as $item) {
				$new = $item->duplicate();
				$this->owner->$relation()->add($new);
			}
		}
		unset($this->owner->_inDuplication);
	}
	
}
