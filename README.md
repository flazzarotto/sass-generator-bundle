README
======

This Symfony3 bundle use `leafo/scssphp` and `koala-framework/sourcemaps` to compile all scss files of a directory to
css in another directory. You can also generate css sourcemaps as well. It is mainly made up of a sass compiler / 
sourcemap generator service and a command line tool.
 
## Set up

1. Run `composer require flazzarotto/sass-generator dev-master`

2. Modify you AppKernel.php:

   ```php
   $bundles = [
     // bunch of other bundles
     new Flazzarotto\SassGeneratorBundle\SassGeneratorBundle(), // <= add this line
   ];
   ```

3. (Optional) You can automatise the sass generation by adding commands to your composer.json
in `scripts > post-install-cmd/post-update-cmd/warmup`. See below for more details about sass generator service and
its command line tool. 

HOW TO USE
==========
The main goal of this package is to generate compiled css files and associated sourcemaps from a list of scss files
in a symfony3 project. This can be very useful if your project must be released on cloud services such as Heroku.
There are to ways to use it :

1. command line: `php bin/console sass:dump [inputFolder:outputFolder] [--source-maps] [--line-numbers]
                    [--precision x] [--format myFormat]`
   
   * **inputFolder:outputFolder** : should be something like "web/scssInputFolder:web/cssOutputFolder"
   * **source-maps** : if set it will generate source maps as well (same folder as css files)
   * **line-number** : will generate CSS with source file line numbers
   * **precision** : set the precision for float numbers (default sass value is 5)
   * **format** : the format wanted in the following: `compact`, `compressed`, `crunched`, `expanded`, `nested`}

2. Service 

   You can use this service if you need to be more flexible than the command tool line or if you intend to create your
   own command. Here's how to initialise the service:
   
   ```php
   // get the service inside a controller
   $sassGenerator = $this->get('sass_generator');
   
   $sassGenerator->init($maps, $lineNumbers, $precision, $format, $input->getArgument('io'));
   ```
   
   Parameters are the same as the command line tool (set options to true to get same result). Then you will be able to
   use the following methods:
   
   ```php
   // generate css files in output folder from all scss files found in input folder.
   $sassGenerator->compileAll();
   
   // get all scss files found in folder
   $files = $sassGenerator->getSourceFiles();
   
   // compile only one file in css
   $sassGenerator->compile($file[0]); // false on failure, path to css file on success
  
   // compile only one file in sourcemap - css must have been generated before 
   $sassGenerator->generateMap($file[0]); // false on failure, path to sourcemap file on success
   
   // get all warnings encountered during compilation or sourcemap generation
   $sassGenerator->getWarnings(); 
   
   // you can re-initialise the service at any time to compile another scss folder
   $sassGenerator->init(/*params*/); 
   ```