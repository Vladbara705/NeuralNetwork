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
     * NeuralNetwork constructor.
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->synapse = 0;
    }

    private function resetSynapseCounter()
    {
        $this->synapse = 0;
    }

    /**
     * @param $hiddenNeurons
     * @param $input
     * @param $iteration
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
            $neurons[$key] = 1 / (1 + exp(-$neuron));
        }
        return $neurons;
    }

    /**
     * @param array $input
     * @param array $output
     * @return false
     */
    public function execute($input = [], $output = [])
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
        $hiddenNeurons = $this->calculateFirstHiddenLayer($hiddenNeurons,true);
        $hiddenNeurons = $this->sigmoid($hiddenNeurons);

        /*************************************
         * Next hidden layers
         *************************************/
        for ($i = 1; $i < $hiddenNeuronsLayersCount;) {
            $i++;
            $this->resetSynapseCounter();
            $hiddenNeurons = $this->calculateNextHiddenLayer($i, $hiddenNeurons, false);

            // Hidden layer with bias
            $this->resetSynapseCounter();
            $hiddenNeurons = $this->calculateNextHiddenLayer($i, $hiddenNeurons,true);
            $hiddenNeurons = $this->sigmoid($hiddenNeurons);
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
        $outputNeurons = $this->calculateOutputLayer($outputNeurons, true);
        $result = $this->sigmoid($outputNeurons);

        return $result;
    }
}
