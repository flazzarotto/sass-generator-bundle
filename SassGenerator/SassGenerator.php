<?php

namespace Flazzarotto\SassGeneratorBundle\SassGenerator;
use Leafo\ScssPhp\Compiler;
use Kwf_SourceMaps_SourceMap;

class SassGenerator {

    private static $formats = ['compact', 'compressed', 'crunched', 'expanded', 'nested'];

    private $cleanCSS,
            $lineNumbers,
            $precision,
            $format,
            $inputDir,
            $outputDir,
            $sourceMaps;

    private $sourceFiles;

    public function __construct(){}

    /**
     * @param $maps boolean true to generate sourcemap, false otherwise
     * @param $lineNumbers boolean true to generate line numbers in CSS, false otherwise
     * @param $precision integer set the precision for float numbers (default sass value=5)
     * @param $format string one of the following values : {'compact', 'compressed', 'crunched', 'expanded', 'nested'}
     * @param $io string paths to input:output folders (semi-column separated values)
     *
     * Set all parameters for compiler and sourcemap generator
     */
    public function init($maps, $lineNumbers, $precision, $format, $io) {

        $this->cleanCSS = !$lineNumbers && $format==='compact';

        if ($maps) {
            $lineNumbers = true;
            $format = "compact";
        }
        else {
            // check format is in list
            $this->checkFormat($format);
        }

        // check input dir is reachable and output dir is writable
        $this->checkDirs($io,$inputDir,$outputDir);

        $this->sourceMaps = $maps;

        $this->lineNumbers = $lineNumbers;

        $this->format = $format;

        $this->precision = $precision;

        $this->inputDir = $inputDir;

        $this->outputDir = $outputDir;

        $sources = glob($this->inputDir ."*.scss");

        $this->sourceFiles = array_combine(
            $sources, array_fill(0,count($sources),
                [   'compiled'=>false,
                    'sourcemap'=>false,
                    'warnings' => []
                ]
            )
        );

    }

    /**
     * @return string[] paths to input files
     * Returns all SCSS source files found in input folder
     */
    public function getSourceFiles() {
        return array_keys($this->sourceFiles);
    }


    /**
     * @param $file string path to SCSS file
     * @return string|boolean path to compiled css file, or false on failure
     * Compiles a single CSS file from given scss source file
     */
    public function compile($file) {

        if (!isset($this->sourceFiles[$file])) {
            throw new \UnexpectedValueException('File '.$file.' not found in input directory.');
        }

        $scss = $this->getCompiler();

        $css = $this->outputDir . preg_replace('#\.scss$#','.css',basename($file));

        $compiled = $scss->compile(file_get_contents($file),$file)
            ." /*# sourceMappingURL=".basename($css).".map */";

        $compiled = preg_replace('#\\n[[:space:]]*\\n#',"\n",$compiled);

        $this->sourceFiles[$file]['compiled'] = (false === file_put_contents($css,$compiled))
                                                ? false
                                                : $css;

        return $this->sourceFiles[$file]['compiled'];
    }

    /**
     * @param $file string path to SCSS file
     * @return string|boolean path to Sourcemap file, or false on failure
     * Generates a single sourcemap file from given scss source file
     */
    public function generateMap($file) {

        if (!isset($this->sourceFiles[$file])) {
            throw new \UnexpectedValueException('File '.$file.' not found in input directory.');
        }

        $css = $this->sourceFiles[$file]['compiled'];

        if ($css === false) {
            throw new \UnexpectedValueException('CSS file not found for source file '.$file);
        }

        $handle = fopen($css, 'r');

        $lNumber = 0;

        $compiled = file_get_contents($css);

        $sourceMapGenerator = Kwf_SourceMaps_SourceMap::createEmptyMap($compiled);

        while (false !== ($line = fgets($handle))) {
            preg_match_all('#[[:space:]]*/\* line ([0-9]+), ([^[:space:]]+) \*/#', $line, $matches);

            $lNumber++;

            if (count($matches) < 3) {
                continue;
            }

            for ($i = 0; $i < count($matches[1]); $i++) {

                if (!isset($matches[2][$i])) {
                    break;
                }

                $originalLine = $matches[1][$i];
                $originalFile = $matches[2][$i];

                // TODO set SCSS folder path relative to CSS folder
                $originalFile = preg_replace('#(.*/)?web/#', '/', $originalFile);

                $sourceMapGenerator->addMapping($lNumber, 0, $originalLine, 0, $originalFile);
            }

        }

        $map = $css . '.map';


        if ($this->cleanCSS) {
            // remove double lines
            $compiled = preg_replace('@[ ]*/[*][ ](?:(?![*]/).)*[*]/[ ]*@s','', $compiled);

            if (false === file_put_contents($css, $compiled)) {
                $this->sourceFiles[$file]["warnings"][] =
                    'Error removing comments from file '.$css;
            }
        }

        $sMap = $sourceMapGenerator->getMapContents();

        $this->sourceFiles[$file]['sourcemap'] = (false === file_put_contents($map,$sMap))
                                                    ? false
                                                    : $map;
    }

    /**
     * Compile all scss source files into css and generates matching sourcemaps if needed
     */
    public function compileAll() {
        foreach ($this->sourceFiles as $file => $data) {
            if (!$this->compile($file)) {
                $this->sourceFiles[$file]["warnings"][] = "Error encountered while compiling $file";
            }
            if ($this->sourceMaps) {
                if (!$this->generateMap($file)) {
                    $this->sourceFiles[$file]["warnings"][] = "Error encountered while generating sourcemap for $file";
                }
            }
        }
    }

    /**
     * @return string[] list of all encountered warnings during compilation / sourcemap generation
     */
    public function getWarnings() {
        $warnings = [];

        foreach ($this->sourceFiles as $file => $data) {
            $warnings = array_merge($warnings,$data['warnings']);
        }

        return $warnings;
    }

    /**
     * @return Compiler
     */
    private function getCompiler() {
        $scss = new Compiler();

        if ($this->lineNumbers) {
            $scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
        }

        if ($this->precision) {
            $scss->setNumberPrecision($this->precision);
        }

        if ($this->format) {
            $scss->setFormatter('Leafo\\ScssPhp\\Formatter\\' . ucfirst($this->format));
        }

        $scss->addImportPath($this->inputDir);

        return $scss;
    }


    /**
     * @param $io string path to input:ouput
     * @param $inputDir string will be populated with parsed input folder
     * @param $outputDir string will be populated with parsed output folder
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     *
     * Checks if input folder is reachable and output folder writable and populate out variables
     */
    private function checkDirs($io, &$inputDir, &$outputDir) {

        if (!preg_match('#([^:]+):([^:]+)#',$io,$matches)) {
            throw new \InvalidArgumentException('Missing or invalid input:ouput argument');
        }

        list(,$inputDir,$outputDir) = $matches;

        $inputDir = preg_replace('#/+#','/',$inputDir.'/');
        $outputDir = preg_replace('#/+#','/',$outputDir.'/');

        if (!is_dir($outputDir)) {
            mkdir($outputDir,0777,true);
        }

        if (!is_dir($inputDir)) {
            throw new \UnexpectedValueException('Input value (`'.$inputDir.'`) is not a directory');
        }
        if (!is_dir($outputDir)) {
            throw new \UnexpectedValueException('Output value (`'.$outputDir.'`) is not a directory');
        }
        else if (!is_writable($outputDir)) {
            throw new \UnexpectedValueException('Output directory seems not to be writable');
        }

    }


    /**
     * @param $format string compilation format
     * @throws \UnexpectedValueException
     *
     */
    private function checkFormat($format) {

        if(!in_array($format,self::$formats)) {
            throw new \UnexpectedValueException('Unrecognize format. Accepted values are: '.implode(', ', self::$formats).'.');
        }

    }

}
