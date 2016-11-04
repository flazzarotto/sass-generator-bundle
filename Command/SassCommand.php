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
            ->setName('sass:generate')
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

        $lineNumbers = $input->getOption('line-numbers');

        $maps = $input->getOption('source-maps');

        $sassGenerator = $this->getContainer()->get('sass_generator');

        $sassGenerator->init($maps, $lineNumbers, $precision, $format, $input->getArgument('io'));

        foreach ($sassGenerator->getSourceFiles() as $file ) {

            $output->writeln(['<question>','Generating CSS '.($input->getOption('source-maps')?'and sourcemap ':'')
                                .'from '.$file.'...','</question>']);

            $css = $sassGenerator->compile($file);

            if (false === $css) {
                $output->writeln(['<error>','Error generating compiled CSS from '.$file,'</error>']);
                continue;
            }

            $output->writeln(['<info>','File '.$css.' successfully generated from '.$file,'</info>']);

            if ($input->getOption('source-maps')) {
                $map = $sassGenerator->generateMap($file);

                if (false === $map) {
                    $output->writeln(['<error>', 'Error generating CSS sourcemap file from ' . $css, '</error>']);
                    continue;
                }

                $output->writeln(['<info>', 'Sourcemap ' . $map . ' successfully generated from ' . $css,
                    '</info>']);
            }

        }

        $warnings = $sassGenerator->getWarnings();
        if (count($warnings)) {

            $output->writeln(['<comment>', 'Following warnings encountered while generating CSS:', '</comment>']);
            $output->writeln($warnings);

        }

    }



}