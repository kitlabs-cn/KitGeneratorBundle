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
			 
			            new Kit\GeneratorBundle\KitGeneratorBundle(),
			        }
	 			}
	       
	    }
	 
	    // ...
	}

## Usage

	kit:doctrine:generate:crud
	kit:doctrine:generate:form
	//theme
	kit:doctrine:generate:crud:theme --theme=Pintuer
