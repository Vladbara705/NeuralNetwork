<?php

namespace network;

use exceptions\ParameterNotFoundException;
use helpers\Config;

/**
 * Class NeuralNetwork
 * @author vlad <vladbara705@gmail.com>
 * @package network
 */
class NeuralNetwork
{
    /**
     * @var array|false
     */
    private $config;
    /**
     * @var int
     */
    private $synapse;
    /**
     * @var false|mixed
     */
    public $debug;

    /**
     * NeuralNetwork constructor.
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->config = new Config();
        $this->synapse = 0;
        $this->debug = $debug;
    }

    private function resetSynapseCounter()
    {
        $this->synapse = 0;
    }

    /**
     * @param $hiddenNeurons
     * @param $input
     * @param $isBias
     * @return mixed
     */
    private function calculateFirstHiddenLayer($hiddenNeurons, $isBias, $input = [])
    {
        $hiddenNeuronsCount = $this->config->getParameter('HIDDEN_NEURONS_LAYER_1');
        if (empty($hiddenNeuronsCount)) throw new ParameterNotFoundException();

        for ($b = 0; $b <= $hiddenNeuronsCount - 1; $b++) {
            if ($isBias) {
                $bias = $this->config->getParameter('BIAS');
                $weight = $this->config->getParameter('WEIGHT_BIAS_1_HIDDEN_LAYER_' . $this->synapse);
                if (empty($weight) || empty($bias)) throw new ParameterNotFoundException();
                $hiddenNeurons[$b] += $bias * $weight;
            } else {
                $weight = $this->config->getParameter('WEIGHT_1_HIDDEN_LAYER_' . $this->synapse);
                if (empty($weight)) throw new ParameterNotFoundException();
                if (isset($hiddenNeurons[$b])) {
                    $hiddenNeurons[$b] += $input * $weight;
                } else {
                    $hiddenNeurons[$b] = $input * $weight;
                }
            }
            $this->synapse++;
        }
        return $hiddenNeurons;
    }

    /**
     * @param $layer
     * @param $hiddenNeurons
     * @param $isBias
     * @return mixed
     */
    private function calculateNextHiddenLayer($layer, $hiddenNeurons, $isBias)
    {
        $prevIterationNeurons = $hiddenNeurons;
        unset($hiddenNeurons);
        $hiddenNeurons = [];

        if ($isBias) {
            $bias = $this->config->getParameter('BIAS');
            foreach ($prevIterationNeurons as $key => $neuron) {
                $weight = $this->config->getParameter('WEIGHT_BIAS_' . $layer . '_HIDDEN_LAYER_' . $this->synapse);
                $hiddenNeurons[] = $neuron + $bias * $weight;
                $this->synapse++;
            }
        } else {
            $hiddenNeuronsCount = $this->config->getParameter('HIDDEN_NEURONS_LAYER_' . $layer);
            if (empty($hiddenNeuronsCount)) throw new ParameterNotFoundException();

            foreach ($prevIterationNeurons as $key => $neuron) {
                for ($b = 0; $b <= $hiddenNeuronsCount - 1; $b++) {
                    $weight = $this->config->getParameter('WEIGHT_' . $layer . '_HIDDEN_LAYER_' . $this->synapse);
                    if (empty($weight)) throw new ParameterNotFoundException();
                    if (isset($hiddenNeurons[$b])) {
                        $hiddenNeurons[$b] += $neuron * $weight;
                    } else {
                        $hiddenNeurons[$b] = $neuron * $weight;
                    }
                    $this->synapse++;
                }
            }
        }
        return $hiddenNeurons;
    }

    /**
     * @param $outputNeurons
     * @param $isBias
     * @param null $hiddenNeuron
     * @return mixed
     */
    private function calculateOutputLayer($outputNeurons, $isBias, $hiddenNeuron = null)
    {
        $outputNeuronsCount = $this->config->getParameter('OUTPUT_NEURONS');
        if (empty($outputNeuronsCount)) throw new ParameterNotFoundException();

        if ($isBias) {
            $bias = $this->config->getParameter('BIAS');
            foreach ($outputNeurons as $key => $outputNeuron) {
                $weight = $this->config->getParameter('WEIGHT_BIAS_OUTPUT_' . $this->synapse);
                $outputNeurons[$key] = $outputNeuron + $bias * $weight;
                $this->synapse++;
            }
        } else {
            for ($b = 0; $b <= $outputNeuronsCount - 1; $b++) {
                $weight = $this->config->getParameter('WEIGHT_OUTPUT_' . $this->synapse);
                if (isset($outputNeurons[$b])) {
                    $outputNeurons[$b] += $hiddenNeuron * $weight;
                } else {
                    $outputNeurons[$b] = $hiddenNeuron * $weight;
                }
                $this->synapse++;
            }
        }

        return $outputNeurons;
    }

    /**
     * @param $neurons
     * @return mixed
     */
    private function sigmoid($neurons)
    {
        foreach ($neurons as $key => $neuron) {
            $neurons[$key] = round(1 / (1 + exp(-$neuron)), 2);
        }
        return $neurons;
    }

    /**
     * @param array $input
     * @param bool $withBias
     * @return bool|mixed
     */
    public function execute($input = [], $withBias = true)
    {
        if (empty($input)) return false;
        $inputParametersCount = count($input);
        $hiddenNeuronsLayersCount = $this->config->getParameter('HIDDEN_LAYERS');
        if (empty($hiddenNeuronsLayersCount)) throw new ParameterNotFoundException();

        /*************************************
         * Input layers - first hidden layer
         *************************************/
        $hiddenNeurons = [];
        for ($i = 0; $i <= $inputParametersCount - 1; $i++) {
            $hiddenNeurons = $this->calculateFirstHiddenLayer($hiddenNeurons, false, $input[$i]);
        }

        // First hidden layer with bias
        $this->resetSynapseCounter();
        if ($withBias) {
            $hiddenNeurons = $this->calculateFirstHiddenLayer($hiddenNeurons,true);
        }
        $hiddenNeurons = $this->sigmoid($hiddenNeurons);
        $intermediateCoefficients[] = $hiddenNeurons;

        /*************************************
         * Next hidden layers
         *************************************/
        for ($i = 1; $i < $hiddenNeuronsLayersCount;) {
            $i++;
            $this->resetSynapseCounter();
            $hiddenNeurons = $this->calculateNextHiddenLayer($i, $hiddenNeurons, false);

            // Hidden layer with bias
            $this->resetSynapseCounter();
            if ($withBias) {
                $hiddenNeurons = $this->calculateNextHiddenLayer($i, $hiddenNeurons,true);
            }
            $hiddenNeurons = $this->sigmoid($hiddenNeurons);
            $intermediateCoefficients[] = $hiddenNeurons;
        }

        /*************************************
         * Output layer
         *************************************/
        $outputNeurons = [];
        $this->resetSynapseCounter();
        foreach ($hiddenNeurons as $hiddenNeuron) {
            $outputNeurons = $this->calculateOutputLayer($outputNeurons, false, $hiddenNeuron);
        }

        // Output layer with bias
        $this->resetSynapseCounter();
        if ($withBias) {
            $outputNeurons = $this->calculateOutputLayer($outputNeurons, true);
        }
        $outputNeurons = $this->sigmoid($outputNeurons);

        if ($this->debug) {
            return [
                'result' => $outputNeurons,
                'intermediateCoefficients' => array_reverse($intermediateCoefficients, true)
            ];
        }

        return $outputNeurons;
    }
}
