<?php

require __DIR__ . '/vendor/autoload.php';

use network\NeuralNetwork;

class TrainNetwork
{
    public function __construct()
    {
        $this->neuralNetwork = new NeuralNetwork();
    }

    public function execute()
    {
        $result = $this->neuralNetwork->execute([0,1], []);
        var_dump($result);
    }
}

$train = new TrainNetwork();
$train->execute();

