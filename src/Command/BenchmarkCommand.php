<?php

/**
 * Copyright 2017, Egor Burykin <c5.Jett@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Jett\JSONEntitySerializerBundle\Service\Serializer as JettSerializer;

class BenchmarkCommand extends Command
{
    private $symfonySerializer;

    private $jettSerializer;

    private $entityManager;


    public function __construct(JettSerializer $jettSerializer, EntityManagerInterface $entityManager)
    {
        $encoders = array(new JsonEncoder());
        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(['__initializer__', '__cloner__', '__isInitialized__']);
        $this->symfonySerializer = new SymfonySerializer([$normalizer], $encoders);
        $this->jettSerializer = $jettSerializer;

        parent::__construct('app:benchmark');

        $this->entityManager = $entityManager;
    }


    public function serializerBenchmark($times, $single = true, $jett = true)
    {
        $this->entityManager->clear();
        if ($single) {
            $toSerialize = $this->entityManager->find(User::class, 1);
        } else {
            $toSerialize = $this->entityManager->getRepository(User::class)->findAll();
        }

        $time = microtime(true);
        for($i = 0; $i < $times; $i++) {
            if ($jett) {
                $this->jettSerializer->serialize($toSerialize);
            } else {
                $this->symfonySerializer->serialize($toSerialize, 'json');
            }

        }
        $time = microtime(true) - $time;

        if ($jett) {
            $this->jettSerializer->cleanCache();
        }

        return $time;
    }

    /**
     * This is general scenario.
     * - Run-and-die process.
     * - One entity to serialize
     * - Entity is not loaded to doctrine cache.
     *
     * @param $times
     * @return float
     */
    public function singleItemBenchmark($times = 1)
    {
        //Order here does not matter because we cleared cache
        $time2 = $this->serializerBenchmark($times);
        $time1 = $this->serializerBenchmark($times, true, false);

        return $time1/$time2;
    }


    public function collectionBenchmark($times = 1) {
        //Order here does not matter because we cleared cache
        $time2 = $this->serializerBenchmark($times, false);
        $time1 = $this->serializerBenchmark($times, false, false);

        return $time1/$time2;
    }

    public function result($output, $rate, $times)
    {
        $rate =  round($rate/$times, 1);
        if ($rate > 1) {
            $output->writeln('<info>Jett serializer is ~ '. $rate.'x faster</info>');
        } else {
            $output->writeln('<error>Jett serializer is ~ '. $rate.'x slower</error>');
        }
        $output->writeln('');
    }

    public function printScenario(OutputInterface $output, $lines)
    {
        $output->writeln('<fg=yellow;options=bold>'.array_shift($lines).'</>');
        $output->write($lines, true);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        php_uname();
        //Scenario 1
        $this->printScenario($output, [
            'Scenario 1:',
            ' * Run-and-die process',
            ' * One entity to serialize',
            ' * Entity is not loaded to doctrine cache',
        ]);
        $times = 100;
        $rate = 0;
        for($i = 0; $i < $times; $i++) {
            $rate += $this->singleItemBenchmark();
        }
        $this->result($output, $rate, $times);

        // Scenario 2
        $this->printScenario($output, [
            'Scenario 2:',
            ' * Web-socket daemon (continues execution)',
            ' * One entity to serialize',
            ' * Entity is loaded to doctrine cache once'
        ]);
        $times = 10;
        $rate = 0;
        for($i = 0; $i < $times; $i++) {
            $rate += $this->singleItemBenchmark(100);
        }
        $this->result($output, $rate, $times);

        // Scenario 3
        $this->printScenario($output, [
            'Scenario 3:',
            ' * Run-and-die process',
            ' * Collection of entities to serialize',
            ' * Collection is not loaded to doctrine cache',
        ]);
        $times = 10;
        $rate = 0;
        for($i = 0; $i < $times; $i++) {
            $rate += $this->collectionBenchmark();
        }
        $this->result($output, $rate, $times);

        // Scenario 4
        $this->printScenario($output, [
            'Scenario 4:',
            ' * Web-socket daemon (continues execution)',
            ' * Collection of entities to serialize',
            ' * Collection is loaded to doctrine cache once'
        ]);
        $times = 1;
        $rate = 0;
        for($i = 0; $i < $times; $i++) {
            $rate += $this->collectionBenchmark(100);
        }
        $this->result($output, $rate, $times);

    }
}