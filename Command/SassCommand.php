<?php

namespace Flazzarotto\SassGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SassCommand extends ContainerAwareCommand
{

    /**
     * Creates command with description
     */
    protected function configure()
    {
        $this
            ->setName('sass:dump')
            ->setDescription('Generate css in output directory from sass files in input directory')
            ->addArgument('io',InputArgument::OPTIONAL,'input_dir:output_dir','web/scss:web/css')
            ->addOption('format','f',InputOption::VALUE_REQUIRED,'format of generated file','compact')
            ->addOption('line-numbers',null,InputOption::VALUE_NONE,'handle line numbers')
            ->addOption('source-maps',null,InputOption::VALUE_NONE,'generate source maps')
            ->addOption('precision',null,InputOption::VALUE_REQUIRED,'generate source maps',5)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $format = $input->getOption('format');

        $precision = $input->getOption('precision');

        $originalLineNumbers = $input->getOption('line-numbers');

        $maps = $input->getOption('source-maps');

        $sassGenerator = $this->getContainer()->get('sass_generator');

        $sassGenerator->init($maps, $originalLineNumbers, $precision, $format, $input->getArgument('io'));

        foreach ($sassGenerator->getSourceFiles() as $file ) {

            $output->writeln(['','Generating CSS '.($input->getOption('source-maps')?' and Sourcemap ':'')
                                .'from '.$file.'...','']);

            $css = $sassGenerator->compile($file);

            if (false === $css) {
                $output->writeln(['','Error generating compiled CSS from '.$file,'']);
                continue;
            }

            $output->writeln(['','File '.$css.' successfully generated from '.$file,'']);

            if ($input->getOption('source-maps')) {
                $map = $sassGenerator->generateMap($file);

                if (false === $map) {
                    $output->writeln(['', 'Error generating CSS sourcemap file from ' . $css, '']);
                    continue;
                }

                $output->writeln(['', 'Sourcemap ' . $map . ' successfully generated from ' . $css, '']);
            }

        }

        $warnings = $sassGenerator->getWarnings();
        if (count($warnings)) {

            $output->writeln(['', 'Following warnings encountered while generating CSS:', '']);
            $output->writeln($warnings);

        }

    }



}