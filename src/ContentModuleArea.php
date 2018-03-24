<?php

namespace ChristopherBolt\ContentModules;

use SilverStripe\ORM\DataObject;
use ChristopherBolt\PublishWithMe\PublishWithMe;
use SilverStripe\Versioned\Versioned;

class ContentModuleArea extends DataObject {
	
	private static $table_name = 'ContentModuleArea';
	
	private static $has_many = array(
		'Modules' => ContentModule::class,
	);
	
	private static $search_index = array(
		'Modules'
	);
	
	private static $owns = array(
		'Modules'
	);
	
	private static $extensions = array(
		PublishWithMe::class,
    	Versioned::class,
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
