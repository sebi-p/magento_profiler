<?php

/**
 * Class Ecocode_Profiler_Block_Bag
 *
 * @method getBag
 */
class Ecocode_Profiler_Block_BackTrace
    extends Mage_Core_Block_Template
{
    public function _construct()
    {
        $this->setTemplate('ecocode_profiler/back-trace.phtml');
        parent::_construct();
    }

    public function getTraceId()
    {
        $id = $this->getData('id');
        if (!$id) {
            $this->setData('id', uniqid());
        }

        return $id;
    }

    public function getTrace()
    {
        $trace = $this->getData('trace');
        if (!$trace) {
            return [];
        }

        return $trace;
    }
}
