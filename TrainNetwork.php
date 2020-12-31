<?php

require __DIR__ . '/vendor/autoload.php';

use network\NeuralNetwork;

/**
 * Class TrainNetwork
 * @author vlad <vladbara705@gmail.com>
 */
class TrainNetwork
{
    /**
     * @var NeuralNetwork
     */
    private $neuralNetwork;

    /**
     * TrainNetwork constructor.
     */
    public function __construct()
    {
        $this->neuralNetwork = new NeuralNetwork();
    }

    public function execute()
    {
        $result = $this->neuralNetwork->execute([0, 1], false);
        var_dump($result);
    }
}

$train = new TrainNetwork();
$train->execute();

