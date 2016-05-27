<?

CCurrencyLang::disableUseHideZero();

$orderId = (int)$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"];

if ($orderId > 0)
{
	/** @var \Bitrix\Sale\Order $order */
	$order = \Bitrix\Sale\Order::load($orderId);
	if ($order)
	{
		/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $order->getPaymentCollection();
		if ($paymentCollection)
		{
			/** @var \Bitrix\Sale\Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				if (!$payment->isInner())
					break;
			}
			if ($payment)
			{
				$context = \Bitrix\Main\Application::getInstance()->getContext();
				$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
				if ($_REQUEST['pdf'])
				{
					$template = $service->initiatePay($payment, $context->getRequest());
					if ($_REQUEST['GET_CONTENT'] == 'Y')
					{
						$result = $service->initiatePay($payment, $context->getRequest(), \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
						if ($result->isSuccess())
							return $result->getTemplate();
					}
					else
					{
						$result = $service->initiatePay($payment, $context->getRequest());
					}
				}
				else
				{
					$result = $service->initiatePay($payment, $context->getRequest());
				}
			}
			CCurrencyLang::enableUseHideZero();
		}
	}
}
?>