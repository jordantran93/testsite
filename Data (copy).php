<?php

namespace Vnext\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 *
 * @package Vnext\Sales\Helper
 */
class Data extends AbstractHelper
{
    const URL_ENDPOINT_CANCEL_ORDER_API = 'tpoint/general/api_enpoint_url';
    const APP_CODE_API = 'tpoint/general/app_code';
    const REGISTER_NUMBER_API = 'tpoint/general/register_no';
    const CANCEL_TYPE_API = 'tpoint/general/cancel_kbn';
    const POINT_TYPE = 'tpoint/general/point_kbn';
    const RESPONSE_STATUS_ERROR = "NG";
    const RESPONSE_STATUS_OK = "00";
    const RESPONSE_STATUS_ACCEPT = "000";

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var \Vnext\Sales\Model\ResourceModel\Tpoint\CollectionFactory
     */
    protected $_tpointCollectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param Curl $curl
     * @param \Vnext\Sales\Model\ResourceModel\Tpoint\CollectionFactory $tpointCollectionFactory
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Vnext\Sales\Model\ResourceModel\Tpoint\CollectionFactory $tpointCollectionFactory,
        DateTime $dateTime,
        TimezoneInterface $timezone
    ) {
        $this->_curl = $curl;
        $this->_tpointCollectionFactory = $tpointCollectionFactory;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * get Service Name List
     * @return array
     */
    public function getServiceNameList()
    {
        $collection = $this->_tpointCollectionFactory->create();
        $serviceName = array();
        if($collection->getSize() > 0){
            foreach ($collection->getData() as $key => $data) {
               $serviceName[$key]['value'] = $data['service_code'];
               $serviceName[$key]['label'] = $data['service_name'];
            }
            return $serviceName;
        }
        return array();
    }

    /**
     * Send request api cancel order tpoint
     * @param $order
     * @return mixed|null
     * @throws \Zend_Json_Exception
     */
    public function sendRequestApiCancelOrderTpoint($order)
    {
        $uri = $this->getUrlEnpointTpointCancel();
        $data = [
            "app_code" => $this->getAppCode(),
            "mem_id" => $order->getSkylarkId(),
            "shori_date" => $this->timezone->date()->format('Ymd'),
            "shori_time" => $this->timezone->date()->format('His'),
            "eigyou_date" => $this->timezone->date()->format('Ymd'),
            "serial_no" => "TO" . $order->getIncrementId(),
            "register_no" => $this->getRegisterNumber(),
            "denpyo_no" => $order->getId(),
            "point_kbn" => (int)$this->getPointType(),
            "cancel_kbn" => (int)$this->getCancelType(),
            "kangen_point" => (int)$order->getPointUsed(),
            "fuyo_point" => (int)$order->getPointAdded(),
            "uriage_kin" => number_format($order->getGrandTotal(),2)
        ];
        $body = \Zend_Json::encode($data);
        $this->_curl->addHeader('Content-Type', 'application/json');
        $this->_curl->post($uri,$body);
        $response = $this->_curl->getBody();
        $dataResponse = \Zend_Json::decode($response);
        return $dataResponse;
    }

    /**
     * get url end point api cancel order
     * @return mixed
     */
    public function getUrlEnpointTpointCancel()
    {
        return $this->scopeConfig->getValue(
            self::URL_ENDPOINT_CANCEL_ORDER_API,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * get app_code config value
     *
     * @return mixed
     */
    public function getAppCode()
    {
        return $this->scopeConfig->getValue(
            self::APP_CODE_API,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * get register_no config value
     *
     * @return mixed
     */
    public function getRegisterNumber()
    {
        return $this->scopeConfig->getValue(
            self::REGISTER_NUMBER_API,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * get cancel_kbn config value
     *
     * @return mixed
     */
    public function getCancelType()
    {
        return $this->scopeConfig->getValue(
            self::CANCEL_TYPE_API,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * get point_kbn config value 73 for use and 74 for cancel
     *
     * @return mixed
     */
    public function getPointType()
    {
        return $this->scopeConfig->getValue(
            self::POINT_TYPE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
