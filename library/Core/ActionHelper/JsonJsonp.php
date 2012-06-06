<?php
class Core_ActionHelper_JsonJsonp extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Suppress exit when sendJson() called
     * @var boolean
     */
    public $suppressExit = false;

    /**
     * Create JSON response
     *
     * Encodes and returns data to JSON. Content-Type header set to
     * 'application/json', and disables layouts and viewRenderer (if being
     * used).
     *
     * @param  mixed   $data
     * @param  boolean $keepLayouts
     * @param  boolean|array $keepLayouts
     * NOTE:   if boolean, establish $keepLayouts to true|false
     *         if array, admit params for Zend_Json::encode as enableJsonExprFinder=>true|false
     *         if $keepLayouts and parmas for Zend_Json::encode are required
     *         then, the array can contains a 'keepLayout'=>true|false
     *         that will not be passed to Zend_Json::encode method but will be passed
     *         to Zend_View_Helper_Json
     * @throws Zend_Controller_Action_Helper_Json
     * @return string
     */
    public function encodeJson($data, $keepLayouts = false)
    {
        /**
         * @see Zend_View_Helper_Json
         */
        require_once 'Zend/View/Helper/Json.php';
        $jsonHelper = new Zend_View_Helper_Json();
        $data = $jsonHelper->json($data, $keepLayouts);

        if (!$keepLayouts) {
            /**
             * @see Zend_Controller_Action_HelperBroker
             */
            require_once 'Zend/Controller/Action/HelperBroker.php';
            Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
        }

        return $data;
    }

    /**
     * Encode JSON response and immediately send
     *
     * @param  mixed   $data
     * @param  boolean|array $keepLayouts
     * NOTE:   if boolean, establish $keepLayouts to true|false
     *         if array, admit params for Zend_Json::encode as enableJsonExprFinder=>true|false
     *         if $keepLayouts and parmas for Zend_Json::encode are required
     *         then, the array can contains a 'keepLayout'=>true|false
     *         that will not be passed to Zend_Json::encode method but will be passed
     *         to Zend_View_Helper_Json
     * @return string|void
     */
    public function sendJson($data, $keepLayouts = false)
    {
        $data = $this->encodeJson($data, $keepLayouts);
        $request  = $this->getRequest();
        $response = $this->getResponse();
        
        $callback = $request->getParam('callback', false);
        
        if($callback!==false)
        {
            $data = $callback . '(' . $data . ');';
        }
        
        $response->setBody($data);

        if (!$this->suppressExit) {
            $response->sendResponse();
            exit;
        }

        return $data;
    }

    /**
     * Strategy pattern: call helper as helper broker method
     *
     * Allows encoding JSON. If $sendNow is true, immediately sends JSON
     * response.
     *
     * @param  mixed   $data
     * @param  boolean $sendNow
     * @param  boolean $keepLayouts
     * @return string|void
     */
    public function direct($data, $sendNow = true, $keepLayouts = false)
    {
        if ($sendNow) {
            return $this->sendJson($data, $keepLayouts);
        }
        return $this->encodeJson($data, $keepLayouts);
    }
}