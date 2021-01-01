<?php

require __DIR__ . '/vendor/autoload.php';

use helpers\Config;
use network\NeuralNetwork;

/**
 * Class TrainNetwork
 * @author vlad <vladbara705@gmail.com>
 */
class TrainNetwork
{
    const SPEED_TRAIN = 0.7;
    const MOMENT_TRAIN = 0.3;

    /**
     * @var NeuralNetwork
     */
    private $neuralNetwork;
    /**
     * @var Config
     */
    private $config;

    /**
     * TrainNetwork constructor.
     */
    public function __construct()
    {
        $this->neuralNetwork = new NeuralNetwork();
        $this->neuralNetwork->debug = true;
        $this->config = new Config();
    }

    /**
     * @param $outActual
     * @return float|int
     */
    private function getErrorPercent($outActual)
    {
        $result = 1 - $outActual;
        $result = pow($result, 2);
        return $result / 1;
    }

    /**
     * @param $outActual
     * @param $outIdeal
     * @return float|int
     */
    private function getDeltaOutput($outActual, $outIdeal)
    {
        return ($outIdeal - $outActual) * ((1 - $outActual) * $outActual);
    }

    /**
     * @param $outActual
     * @param $weights
     * @param $deltaNeurons
     * @return float|int
     */
    private function getDeltaHiddenNeuron($outActual, $weights, $deltaNeurons)
    {
        $composition = 0;
        foreach ($weights as $key => $weight) {
            $composition += $weight * $deltaNeurons[$key];
        }

        if (empty($weight)) throw new exceptions\ParameterNotFoundException();
        return ((1 - $outActual) * $outActual) * $composition;
    }

    public function execute()
    {   $results = $this->neuralNetwork->execute([1, 0], false);

        /*************************************
         * Delta output
         *************************************/
        foreach ($results['result'] as $result) {
            $errorPercent[] = $this->getErrorPercent($result);
        }

        /*************************************
         * Hidden layers delta
         *************************************/
        $hiddenLayersCount = $this->config->getParameter('HIDDEN_LAYERS', false);
        $outputNeuronsCount = $this->config->getParameter('HIDDEN_LAYERS', false);
        for ($layer = $hiddenLayersCount; $layer >= 1; $layer--) {
            $hiddenNeuronsCount = $this->config->getParameter('HIDDEN_NEURONS_LAYER_' . $layer, false);
            for ($neuron = 0; $neuron <= $hiddenNeuronsCount - 1; $neuron++) {
                $weights = [];
                $deltaOutputs = [];

                if ($hiddenLayersCount == $layer) {
                    $weightNumber = $neuron * $outputNeuronsCount;
                    if ($outputNeuronsCount > 1) {
                        for ($i = 0; $i <= $outputNeuronsCount - 1; $i++) {
                            $weight = $this->config->getParameter('WEIGHT_OUTPUT_' . $weightNumber, false);
                            $weights[] = $weight;
                            $deltaOutput[] = $this->getDeltaOutput($results['result'][$weightNumber], 1);
                            $deltaOutputs[] = $deltaOutput;
                            $grad = $deltaOutput * $results['intermediateCoefficients'][0][$neuron];
                            $grads[] = $grad;

                            $prevWeight = $this->config->getParameter('PREV_WEIGHT_OUTPUT_' . $weightNumber, false);
                            $momentTrain = self::MOMENT_TRAIN * $prevWeight ? $prevWeight : 0;
                            $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
                            $newWeight = $weight + $deltaWeight;
                            $this->config->setParameter('WEIGHT_OUTPUT_' . $weightNumber, $newWeight);
                            $this->config->setParameter('PREV_WEIGHT_OUTPUT_' . $weightNumber, $deltaWeight);
                        }
                    } else {
                        $weight = $this->config->getParameter('WEIGHT_OUTPUT_' . $weightNumber, false);
                        $weights[] = $weight;
                        $deltaOutput = $this->getDeltaOutput($results['result'][0], 1);
                        $deltaOutputs[] = $deltaOutput;
                        $grad = $deltaOutput * $results['intermediateCoefficients'][0][$neuron];
                        $grads[] = $grad;

                        $prevWeight = $this->config->getParameter('PREV_WEIGHT_OUTPUT_' . $weightNumber, false);
                        $momentTrain = self::MOMENT_TRAIN * $prevWeight ? $prevWeight : 0;
                        $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
                        $newWeight = $weight + $deltaWeight;
                        $this->config->setParameter('WEIGHT_OUTPUT_' . $weightNumber, $newWeight);
                        $this->config->setParameter('PREV_WEIGHT_OUTPUT_' . $weightNumber, $deltaWeight);
                    }
                    $weightNumber++;
                }

                if ($hiddenLayersCount == $layer) {
                    $deltaHiddenNeuron[] = $this->getDeltaHiddenNeuron($results['intermediateCoefficients'][0][$neuron], $weights, $deltaOutputs);
                } else {

                }
            }
        }

        var_dump($results);
    }
}

$train = new TrainNetwork();
$train->execute();

//HIDDEN_LAYERS = 1
//HIDDEN_NEURONS_LAYER_1 = 2
//OUTPUT_NEURONS = 1
//BIAS = 1
//
//WEIGHT_1_HIDDEN_LAYER_0 = 0.45
//WEIGHT_1_HIDDEN_LAYER_1 = 0.78
//WEIGHT_1_HIDDEN_LAYER_2 = -0.12
//WEIGHT_1_HIDDEN_LAYER_3 = 0.13
//WEIGHT_OUTPUT_0 = 1.5
//WEIGHT_OUTPUT_1 = -2.3

