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
    const SPEED_TRAIN = 0.1;
    const MOMENT_TRAIN = 0.1;

    /**
     * @var NeuralNetwork
     */
    private $neuralNetwork;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var int
     */
    private $weightNumber;
    /**
     * @var int
     */
    private $weightNumber2;

    /**
     * TrainNetwork constructor.
     */
    public function __construct()
    {
        $this->neuralNetwork = new NeuralNetwork();
        $this->neuralNetwork->debug = true;
        $this->config = new Config();
        $this->weightNumber = 0;
        $this->weightNumber2 = 0;
    }

    private function resetWeightNumberCounter()
    {
        $this->weightNumber = 0;
    }

    /**
     * @param $outIdeal
     * @param $outActual
     * @return float|int
     */
    private function getErrorPercent($outIdeal, $outActual)
    {
        $result = $outIdeal - $outActual;
        $result = pow($result, 2);
        return $result / 1;
    }

    /**
     * @param $outActual
     * @param $outIdeal
     * @return float|int
     */
    private function getDeltaOutput($outIdeal, $outActual)
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
        return ((1 - $outActual) * $outActual) * $composition;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function normalizeData($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, [4,5])) {
                $data[$key] = 1 / strlen($value);
            } else {
                $data[$key] = 1 / $value;
            }
        }
        return $data;
    }

    /**
     * @param $results
     * @param $neuron
     * @return float|int
     */
//    private function updateWeightOnOutputLayer($results, $neuron)
//    {
//        $outputNeuronsCount = Config::getParameter('OUTPUT_NEURONS', false);
//        $weights = [];
//        $deltaOutputs = [];
//
//        for ($i = 0; $i <= $outputNeuronsCount - 1; $i++) {
//            $weight = Config::getParameter('WEIGHT_OUTPUT_' . $this->weightNumber, false);
//            $weights[] = $weight;
//            $this->weightNumber++;
//        }
//
//        for ($i = 0; $i <= $outputNeuronsCount - 1; $i++) {
//            $deltaOutput = $this->getDeltaOutput($results['outIdeal'][$i], $results['result'][$i]);
//            $deltaOutputs[] = $deltaOutput;
//            $grad = $deltaOutput * $results['intermediateCoefficients'][0][$neuron];
//            $prevWeight = Config::getParameter('PREV_WEIGHT_OUTPUT_' . $this->weightNumber2, false);
//            $weight = Config::getParameter('WEIGHT_OUTPUT_' . $this->weightNumber2, false);
//            $momentTrain = $prevWeight ? self::MOMENT_TRAIN * $prevWeight : 0;
//            $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
//            $newWeight = $weight + $deltaWeight;
//            Config::setParameter('WEIGHT_OUTPUT_' . $this->weightNumber, $newWeight);
//            Config::setParameter('PREV_WEIGHT_OUTPUT_' . $this->weightNumber, $deltaWeight);
//            $this->weightNumber2++;
//        }
//
//        return $this->getDeltaHiddenNeuron($results['intermediateCoefficients'][0][$neuron], $weights, $deltaOutputs);
//    }

    private function updateWeightOnOutputLayer($results, $neuron)
    {
        $outputNeuronsCount = Config::getParameter('OUTPUT_NEURONS', false);
        $weights = [];
        $deltaOutputs = [];

        for ($i = 0; $i <= $outputNeuronsCount - 1; $i++) {
            $weight = Config::getParameter('WEIGHT_OUTPUT_' . $this->weightNumber, false);
            $weights[] = $weight;
            $deltaOutput = $this->getDeltaOutput($results['outIdeal'][$i], $results['result'][$i]);
            $deltaOutputs[] = $deltaOutput;

            $grad = $deltaOutput * $results['intermediateCoefficients'][0][$neuron];
            $prevWeight = Config::getParameter('PREV_WEIGHT_OUTPUT_' . $this->weightNumber, false);
            $momentTrain = $prevWeight ? self::MOMENT_TRAIN * $prevWeight : 0;
            $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
            $newWeight = $weight + $deltaWeight;
            Config::setParameter('WEIGHT_OUTPUT_' . $this->weightNumber, $newWeight);
            Config::setParameter('PREV_WEIGHT_OUTPUT_' . $this->weightNumber, $deltaWeight);
            $this->weightNumber++;
        }

        return $this->getDeltaHiddenNeuron($results['intermediateCoefficients'][0][$neuron], $weights, $deltaOutputs);
    }

    /**
     * @param $deltaHiddenNeurons
     * @param $results
     * @param $nextLayer
     * @param $neuron
     * @return float|int
     */
    private function updateWeightHiddenLayer($deltaHiddenNeurons, $results, $nextLayer, $neuron)
    {
        $currentLayer = $nextLayer - 1;
        $intermediateCoefficients = [];
        $i = 0;
        foreach ($results['intermediateCoefficients'] as $result) {
            $layer = count($results['intermediateCoefficients']) - $i;
            $intermediateCoefficients[$layer] = $result;
            $i++;
        }

        $hiddenNeuronsCount = Config::getParameter('HIDDEN_NEURONS_LAYER_' . $nextLayer, false);
        $weights = [];
        for ($i = 0; $i <= $hiddenNeuronsCount - 1; $i++) {
            $weight = Config::getParameter('WEIGHT_' . $nextLayer . '_HIDDEN_LAYER_' . $this->weightNumber, false);
            $weights[] = $weight;
            $this->weightNumber++;
        }
        $deltaNeuron = $this->getDeltaHiddenNeuron($intermediateCoefficients[$currentLayer][$neuron], $weights, $deltaHiddenNeurons);
        $grad = $deltaNeuron * $intermediateCoefficients[$currentLayer][$neuron];

        for ($i = 0; $i <= $hiddenNeuronsCount - 1; $i++) {
            $prevWeight = Config::getParameter('PREV_WEIGHT_' . $nextLayer . '_HIDDEN_LAYER_' . $this->weightNumber2, false);
            $weight = Config::getParameter('WEIGHT_' . $nextLayer . '_HIDDEN_LAYER_' . $this->weightNumber2, false);
            $momentTrain = $prevWeight ? self::MOMENT_TRAIN * $prevWeight : 0;
            $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
            $newWeight = $weight + $deltaWeight;
            Config::setParameter('PREV_WEIGHT_' . $nextLayer . '_HIDDEN_LAYER_' . $this->weightNumber2, $deltaWeight);
            Config::setParameter('WEIGHT_' . $nextLayer . '_HIDDEN_LAYER_' . $this->weightNumber2, $newWeight);
            $this->weightNumber2++;
        }

        return $deltaNeuron;
    }

    /**
     * @param $input
     * @param $outIdeal
     * @param $withBias
     */
    public function execute($input, $withBias, $outIdeal)
    {
        $results = $this->neuralNetwork->execute($input, $withBias, $outIdeal);

        echo ('Ожидаемый ответ:' . $outIdeal[0] . PHP_EOL);
        echo ('Полученный ответ:' . $results['result'][0] . PHP_EOL);
        echo('Ошибка:' . $this->getErrorPercent($outIdeal[0], $results['result'][0]) . PHP_EOL);

        $hiddenLayersCount = Config::getParameter('HIDDEN_LAYERS', false);
        $deltaHiddenNeurons = [];
        for ($layer = $hiddenLayersCount; $layer >= 1; $layer--) {
            $hiddenNeuronsCount = Config::getParameter('HIDDEN_NEURONS_LAYER_' . $layer, false);
            $this->resetWeightNumberCounter();
            $this->weightNumber2 = 0;
            for ($neuron = 0; $neuron <= $hiddenNeuronsCount - 1; $neuron++) {
                /*************************************
                 * If is last hidden layer
                 *************************************/
                if ($hiddenLayersCount == $layer) {
                    $deltaHiddenNeurons[] = $this->updateWeightOnOutputLayer($results, $neuron);
                /*************************************
                * If is NOT last hidden layer
                *************************************/
                } else if ($hiddenLayersCount > 1 && $hiddenLayersCount != $layer) {
                    if (isset($deltaIntermediateNeurons[$layer])) {
                        $deltaHiddenNeurons = $deltaIntermediateNeurons[$layer];
                        $deltaIntermediateNeurons = [];
                    }

                    $nextLayer = $layer + 1;
                    $deltaNeuron = $this->updateWeightHiddenLayer($deltaHiddenNeurons, $results, $nextLayer, $neuron);
                    $deltaIntermediateNeurons[$layer - 1][] = $deltaNeuron;
                }
            }
        }

        $b = 0;
        for ($i = 0; $i <= count($input) - 1; $i++) {
            isset($deltaIntermediateNeurons) ? $deltaHiddenNeurons = $deltaIntermediateNeurons[0] : false;
            foreach ($deltaHiddenNeurons as $deltaNeuron) {
                $weight = Config::getParameter('WEIGHT_1_HIDDEN_LAYER_' . $b, false);
                $grad = $input[$i] *  $deltaNeuron;
                $prevWeight = Config::getParameter('PREV_1_HIDDEN_LAYER_' . $b, false);
                $momentTrain = $prevWeight ? self::MOMENT_TRAIN * $prevWeight : 0;
                $deltaWeight = self::SPEED_TRAIN * $grad + $momentTrain;
                $newWeight = $weight + $deltaWeight;
                Config::setParameter('WEIGHT_1_HIDDEN_LAYER_' . $b, $newWeight);
                Config::setParameter('PREV_1_HIDDEN_LAYER_' . $b, $deltaWeight);
                $b++;
            }
        }
    }

    public function train()
    {
        $data = [
            [[1, 1, 1, 0, 0, 0], [1,0]],
            [[1, 1, 0, 1, 0, 0], [1,0]],
            [[0, 0, 0, 1, 1, 1], [0,1]],
            [[0, 0, 1, 1, 1, 0], [0,1]],
        ];
        $epoch = 3000;
        for ($i = 0; $i <= $epoch; $i++) {
            foreach ($data as $value) {
                $this->execute($value[0], false, $value[1]);
            }
        }


//        $epoch = 20000;
//        for ($i = 0; $i <= $epoch; $i++) {
//            $i = -1;
//            if (($handle = fopen('data/dataset/dataset.csv', "r")) !== FALSE) {
//                while (($dataset = fgetcsv($handle, 1000, ",")) !== FALSE) {
//                    $i++;
//                    if ($i == 0) continue;
//                    if (empty($dataset[0]) || empty($dataset[1]) || empty($dataset[2]) || empty($dataset[3]) || empty($dataset[4]) || empty($dataset[5]) || empty($dataset[6]))
//                        continue;
//                    $data = [
//                        [
//                            $dataset[0],
//                            $dataset[1],
//                            $dataset[2],
//                            $dataset[3],
//                            $dataset[4],
//                            $dataset[5]
//                        ],
//                        json_decode($dataset[6], true)
//                    ];
//
//                    //var_dump($data[0]);
//                    //var_dump($this->normalizeData($data[0]));
//                    $this->execute($this->normalizeData($data[0]), false, $data[1]);
//                }
//            }
//        }
    }
}

$train = new TrainNetwork();
$train->train();


