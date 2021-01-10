<?php

require __DIR__ . '/vendor/autoload.php';

use helpers\Config;
use network\NeuralNetwork;

/**
 * Class TrainNetwork
 * @author vlad <vladbara705@gmail.com>
 */
class TrainNetwork {

    const SPEED_TRAIN = 0.005;
    const MOMENT_TRAIN = 0.005;

    /**
     * @var NeuralNetwork
     */
    private $neuralNetwork;
    /**
     * @var array
     */
    private $deltaHiddenNeurons;
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
        $this->deltaHiddenNeurons = [];
        $this->weightNumber = 0;
        $this->weightNumber2 = 0;
    }

    /**
     * @param $ideal
     * @param $outActual
     * @return float|int
     */
    private function getErrorPercent($ideal, $outActual)
    {
        $result = $ideal - $outActual;
        $result = pow($result, 2);
        return $result / 1;
    }

    /**
     * @param $outActual
     * @param $ideal
     * @return float|int
     */
    private function getDeltaOutput($ideal, $outActual)
    {
        return ($ideal - $outActual) * ((1 - $outActual) * $outActual);
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
     * @param $results
     * @param $neuron
     * @return bool
     */
    private function updateWeightOnLastHiddenLayer($results, $neuron)
    {
        $outputNeuronsCount = Config::getParameter('OUTPUT_NEURONS', false);
        $weights = [];
        $deltaOutputs = [];

        for ($i = 0; $i <= $outputNeuronsCount - 1; $i++) {
            $weight = Config::getParameter('WEIGHT_OUTPUT_' . $this->weightNumber, false);
            $weights[] = $weight;
            $deltaOutput = $this->getDeltaOutput($results['ideal'][$i], $results['result'][$i]);
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

        $this->deltaHiddenNeurons[] = $this->getDeltaHiddenNeuron($results['intermediateCoefficients'][0][$neuron], $weights, $deltaOutputs);
        return true;
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
     * @param $data
     * @return mixed
     */
    private function normalizeData($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, [4,5])) {
                $data[$key] = round(1 / strlen($value), 2);
            } else {
                $data[$key] = round(1 / $value, 2);
            }
        }
        return $data;
    }

    /**
     * @param $data
     * @param $withBias
     * @param $ideal
     */
    private function train($data, $withBias, $ideal)
    {
        $this->deltaHiddenNeurons = [];
        $results = $this->neuralNetwork->execute($data, $withBias, $ideal);

        echo ('Ожидаемый ответ:' . $ideal[0] . PHP_EOL);
        echo ('Полученный ответ:' . $results['result'][0] . PHP_EOL);
        echo ('Ошибка:' . $this->getErrorPercent($ideal[0], $results['result'][0]) . PHP_EOL);

        $hiddenLayersCount = Config::getParameter('HIDDEN_LAYERS', false);
        for ($layer = $hiddenLayersCount; $layer >= 1; $layer--) {
            $hiddenNeuronsCount = Config::getParameter('HIDDEN_NEURONS_LAYER_' . $layer, false);
            $this->weightNumber = 0;
            $this->weightNumber2 = 0;
            for ($neuron = 0; $neuron <= $hiddenNeuronsCount - 1; $neuron++) {
                /*************************************
                 * If is last hidden layer
                 *************************************/
                if ($hiddenLayersCount == $layer) {
                    $this->updateWeightOnLastHiddenLayer($results, $neuron);
                }
                /*************************************
                 * If is NOT last hidden layer
                 *************************************/
                if ($hiddenLayersCount > 1 && $hiddenLayersCount != $layer) {
                    if (isset($deltaIntermediateNeurons[$layer])) {
                        $this->deltaHiddenNeurons = $deltaIntermediateNeurons[$layer];
                        $deltaIntermediateNeurons = [];
                    }

                    $nextLayer = $layer + 1;
                    $deltaNeuron = $this->updateWeightHiddenLayer($this->deltaHiddenNeurons, $results, $nextLayer, $neuron);
                    $deltaIntermediateNeurons[$layer - 1][] = $deltaNeuron;
                }
            }
        }

        $b = 0;
        for ($i = 0; $i <= count($data) - 1; $i++) {
            isset($deltaIntermediateNeurons) ? $this->deltaHiddenNeurons = $deltaIntermediateNeurons[0] : false;
            foreach ($this->deltaHiddenNeurons as $deltaNeuron) {
                $weight = Config::getParameter('WEIGHT_1_HIDDEN_LAYER_' . $b, false);
                $grad = $data[$i] *  $deltaNeuron;
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

    public function execute()
    {
//        $dataSet = [
//            [
//                'data' => [1, 1, 1, 0, 0, 0],
//                'ideal' => [1,0]
//            ],
//            [
//                'data' => [1, 1, 0, 1, 0, 0],
//                'ideal' => [1,0]
//            ],
//            [
//                'data' => [0, 0, 0, 1, 1, 1],
//                'ideal' => [0,1]
//            ],
//            [
//                'data' => [0, 0, 1, 1, 1, 0],
//                'ideal' => [0,1]
//            ],
//        ];
//
//        $maxEpoch = 3000;
//        for ($epoch = 0; $epoch <= $maxEpoch; $epoch++) {
//            foreach ($dataSet as $data) {
//                $this->train($data['data'], false, $data['ideal']);
//            }
//        }

        $epoch = 20000;
        for ($i = 0; $i <= $epoch; $i++) {
            $b = -1;
            if (($handle = fopen('data/dataset/dataset.csv', "r")) !== FALSE) {
                while (($dataset = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $b++;
                    if ($b == 0) continue;
                    if (empty($dataset[0]) || empty($dataset[1]) || empty($dataset[2]) || empty($dataset[3]) || empty($dataset[4]) || empty($dataset[5]) || empty($dataset[6]))
                        continue;
                    $data = [
                        [
                            $dataset[0],
                            $dataset[1],
                            $dataset[2],
                            $dataset[3],
                            $dataset[4],
                            $dataset[5]
                        ],
                        json_decode($dataset[6], true)
                    ];
                    $this->train($this->normalizeData($data[0]), false, $data[1]);
                }
            }
        }
    }
}

$train = new TrainNetwork();
$train->execute();


