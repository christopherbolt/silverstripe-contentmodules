# ContentModules #
A framework for adding modular content to a page.
All kinds of different content types can be added and re-ordered on a page. This is similar to the WidgetArea module but focused on content.
Content Modules will be published and unpublished when the page is published/unpublished, ie. changes to content modules will only be published when the page is published which makes this module fully compatible with Workflow.
CMS editors can change the content type of a module in a similar why to how you can change the the type of a page in the CMS.

## Requirements ##
SS 4.x see 1.0 branch for SS 3.x

## Creating Modules ##
No content modules are included, you must create your own.

You must create at least one ContentModule type.

Here is how you might code a basic text content module:

mysite/code/modules/TextModule.php:
```
<?php

use ChristopherBolt\ContentModules\ContentModule;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class TextModule extends ContentModule {
		
	private static $db = array(
		'Title' => 'Varchar',
		'Content' => 'HTMLText'
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldsToTab('Root.Main', array(
			TextField::create('Title', 'Title'),
			HTMLEditorField::create('Content', 'Content')
		));		
		return $fields;
	}
}
```

themes/mytheme/templates/modules/TextModule.ss:
```
<section class="module $ClassName">
	<h2>$Title</h2>
	$Content
</section>
```

## Adding modules to your page ##
Add the ModularPageExtension extension to your page:
```
use ChristopherBolt\ContentModules\ModularPageExtension;

class Page extends SiteTree {
	private static $extensions = array(
        ModularPageExtension::class
	);
```

You can now create and add modules to the page in the CMS.

To display the modules in your page template just use:
```
$Modules
```

## Multiple module areas on one page ##
By default the ModularPageExtension adds a ContentModuleArea named "Modules".
You can add additional ContentModuleArea areas:
```
use ChristopherBolt\ContentModules\ContentModuleArea;
use ChristopherBolt\ContentModules\ModularPageExtension;

class TwoColumnPage extends Page {
	private static $has_one = array(
        "RightColumn" => ContentModuleArea::class
	);
    private static $owns = array(
        "RightColumn"
    );
	private static $extensions = array(
        ModularPageExtension::class
	);
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->addFieldsToTab('Root.RightColumn', $this->getModularCMSFields('RightColumn', 'Right Column'));
				
		return $fields;
	}
}
```

And you can display your "RightColumn" content modules in your page template with $RightColumn

## Customise how modules are stacked in your template ##
By default modules are simply stacked one after the other, but you might want to stack them in a list:

themes/mytheme/templates/ContentModuleArea.ss:
```
<ul>
<% loop $Modules %>
<li>$forLoop</li>
<% end_loop %>
</ul>
```

If you wanted to stack different ContentModuleAreas on your page in different ways then you may prefer to add the loop to your page template:
```
<% with $RightColumn %>
<ul>
<% loop $Modules %>
<li>$forLoop</li>
<% end_loop %>
</ul>
<% end_with %>
```

## Controlling which module types are allowed on a page ##
You can restrict what types of content modules can be added to a page, or if you have multiple module areas on your page you can set restrictions for each area.

If you only have the default "Modules" module area or if you have multiple module areas and want to set the same restrictions for all of them:
```
	private static $allowed_modules = array(
        TextModule::class,
        ImageModule::class,
        StaffProfileModule::class
	);
```

If you have multiple module areas you can set restrictions for each one like this:
```
	private static $allowed_modules = array(
		'Modules' = array(
			TextModule::class,
			StaffProfileModule::class
		),
		'RightColumn' = array(
			ImageModule::class,
			RightTextModule::class,
			LinksModule::class
		),
	);
```

### Installation ###
```
composer require christopherbolt/silverstripe-contentmodules ^2
```
