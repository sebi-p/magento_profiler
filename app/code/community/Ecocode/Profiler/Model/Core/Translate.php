<?php

/**
 * @author ecocode GmbH <jk@ecocode.de>
 * @author Justus Krapp <jk@ecocode.de>
 */
class Ecocode_Profiler_Model_Core_Translate extends Mage_Core_Model_Translate
{
    protected $currentMessage = null;

    public function translate($args)
    {
        $_args                = $args;
        $this->currentMessage = [
            'locale' => $this->_locale,
            'module' => null,
            'trace'  => []
        ];

        $text = array_shift($_args);

        if ($text instanceof Mage_Core_Model_Translate_Expr) {
            $this->currentMessage['module'] = $text->getModule();
        }

        $translation = parent::translate($args);
        if ($translation === '') {
            //@TODO log
            return $translation;
        }

        if (@vsprintf($this->currentMessage['translation'], $_args) === false) {
            $this->currentMessage['state'] = 'invalid';
            $this->addTrace();
        }

        $this->currentMessage['parameters']  = $_args;
        $this->currentMessage['translation'] = $translation;

        $this->log();
        return $translation;
    }

    public function _getTranslatedString($text, $code)
    {
        $this->currentMessage['text'] = $text;
        $this->currentMessage['code'] = $code;

        $translated = parent::_getTranslatedString($text, $code);
        if (array_key_exists($code, $this->_data)) {
            $state = 'translated';
        } elseif (array_key_exists($text, $this->_data)) {
            $state = 'fallback';
            $this->addTrace();
        } else {
            $state = 'missing';
            $this->addTrace();
        }

        $this->currentMessage['state']       = $state;
        $this->currentMessage['translation'] = $translated;

        return $translated;
    }

    protected function log()
    {
        Mage::getSingleton('ecocode_profiler/collector_translationDataCollector')
            ->logTranslation(
                $this->currentMessage['locale'],
                $this->currentMessage['code'],
                $this->currentMessage['text'],
                $this->currentMessage['translation'],
                $this->currentMessage['state'],
                $this->currentMessage['parameters'],
                $this->currentMessage['module'],
                $this->currentMessage['trace']
            );
    }

    /**
     * Adding translation data
     *
     * @param array  $data
     * @param string $scope
     * @return Mage_Core_Model_Translate
     */
    protected function _addData($data, $scope, $forceReload = false)
    {
        foreach ($data as $key => $value) {

            /*
            we need to simplify this to properly detect not translated strings
            */
            $key   = $this->_prepareDataString($key);
            $value = $this->_prepareDataString($value);

            $scopeKey = $scope . self::SCOPE_SEPARATOR . $key;

            $this->_data[$scopeKey] = $value;
            if (!isset($this->_data[$key]) && !Mage::getIsDeveloperMode()) {
                $this->_data[$key] = $value;
            }
        }
        return $this;
    }

    protected function addTrace()
    {
        if (!function_exists('debug_backtrace')) {
            return [];
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);

        $trace = array_shift($backtrace);
        while ($backtrace && (!isset($trace['function']) || $trace['function'] !== '__')) {
            $trace = array_shift($backtrace);
        }

        $this->currentMessage['trace'] = array_slice($backtrace, 0, 3);
    }

    protected function shouldRemoveTraceItem()
    {
        if (!isset($data['class'], $data['function'])) {
            return true;
        }

        if (!isset($data['object'])) {
            return false;
        }

        $functionIdent = $data['class'] . '::' . $data['function'];
        if (in_array($functionIdent, $this->ignoredFunctionCalls)) {
            return false;
        }

        $object = $data['object'];
        return ($object instanceof Zend_Db_Adapter_Abstract
            || $object instanceof Varien_Db_Statement_Pdo_Mysql);
    }
}