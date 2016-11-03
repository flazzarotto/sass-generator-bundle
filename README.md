README
======

This bundle use `leafo/scssphp` and `koala-framework/sourcemaps` to compile all scss files of a directory to css in
 another directory. You can also generate css sourcemaps as well.


HOW TO USE
==========
The main goal of this package is to generate compiled css files and associated sourcemaps from a list of scss files
in a symfony3 project. This can be very useful if your project must be released on cloud services such as Heroku.
There are to ways to use it :

1. command line: `php bin/console [inputFolder:outputFolder] [--source-maps] [--line-numbers]
                    [--precision x] [--format myFormat]`
   
   * **source-maps** : if set it will generate source maps as well (same folder as css files)
   * **line-number** : will generate CSS with source file line numbers
   * **precision** : set the precision for float numbers (default sass value is 5)
   * **format** : the format wanted in the following: {'compact', 'compressed', 'crunched', 'expanded', 'nested'}

2. Service 

TODO finish README