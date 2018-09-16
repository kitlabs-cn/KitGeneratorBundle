# KitGeneratorBundle
extend SensioGeneratorBundle


## Installation
 
### Step 1: Download the Bundle
---------------------------
 
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:
 
	
	$ composer require kitlabs/kit-generator-bundle

 
This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.
 
### Step 2: Enable the Bundle
---------------------------
 
Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

	<?php
	// app/AppKernel.php
	 
	// ...
	class AppKernel extends Kernel
	{
	    public function registerBundles()
	    {
	        // ...
	        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
	    	    // ...
	            if ('dev' === $this->getEnvironment()) {
			        // ...
		            $bundles[] = new Kit\GeneratorBundle\KitGeneratorBundle(),
			    }
	 	    }
	    }
	 
	    // ...
	}

## Usage

	 //add table & field option comment 
	 $ php7 bin/console kit:doctrine:generate:entity 

          Welcome to the kitlabs Doctrine2 entity generator

        This command helps you generate Doctrine2 entities.

        First, you need to give the entity name you want to generate.
        You must use the shortcut notation like AcmeBlogBundle:Post.

        The Entity shortcut name: MessageBundle:Test
        Enter table comment: this_is_table_comment #add table option comment 

        Determine the format to use for the mapping information.

        Configuration format (yml, xml, php, or annotation) [annotation]:

        Instead of starting with a blank entity, you can add some fields now.
        Note that the primary key will be added automatically (named id).

        Available types: array, simple_array, json_array, object,
        boolean, integer, smallint, bigint, string, text, datetime, datetimetz,
        date, time, decimal, float, binary, blob, guid.

        New field name (press <return> to stop adding fields): test_id
        Field type [integer]:
        Is nullable [false]:
        Unique [false]:
        Options:comment: this_is_field_comment # add table filed comment

        New field name (press <return> to stop adding fields):

          Entity generation

          created ./src/MessageBundle/Entity/Test.php
        > Generating entity class src/MessageBundle/Entity/Test.php: OK!
        > Generating repository class src/MessageBundle/Repository/TestRepository.php: OK!

          Everything is OK! Now get to work :).

	kit:doctrine:generate:crud # generate controller, router and views
	//theme
	kit:doctrine:generate:crud:theme --theme=Pintuer
