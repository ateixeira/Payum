<?php
namespace Payum\Stripe;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\PaymentFactory as CorePaymentFactory;
use Payum\Core\PaymentFactoryInterface;
use Payum\Stripe\Action\Api\CreateChargeAction;
use Payum\Stripe\Action\Api\ObtainTokenAction;
use Payum\Stripe\Action\CaptureAction;
use Payum\Stripe\Action\FillOrderDetailsAction;
use Payum\Stripe\Action\StatusAction;

class CheckoutPaymentFactory implements PaymentFactoryInterface
{
    /**
     * @var PaymentFactoryInterface
     */
    protected $corePaymentFactory;

    /**
     * @param PaymentFactoryInterface $corePaymentFactory
     */
    public function __construct(PaymentFactoryInterface $corePaymentFactory = null)
    {
        $this->corePaymentFactory = $corePaymentFactory ?: new CorePaymentFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $config = array())
    {
        return $this->corePaymentFactory->create($this->createConfig($config));
    }

    /**
     * {@inheritDoc}
     */
    public function createConfig(array $config = array())
    {
        $config = ArrayObject::ensureArrayObject($config);
        $config->defaults($this->corePaymentFactory->createConfig());

        $config->defaults(array(
            'payum.template.obtain_token' => '@PayumStripe/Action/obtain_checkout_token.html.twig',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.fill_order_details' => new FillOrderDetailsAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.obtain_token' => function (ArrayObject $config) {
                return new ObtainTokenAction($config['payum.template.obtain_token']);
            },
            'payum.action.create_charge' => new CreateChargeAction(),
        ));

        if (false == $config['payum.api']) {
            $config['options.required'] = array('publishable_key', 'secret_key');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['options.required']);

                return new Keys($config['publishable_key'], $config['secret_key']);
            };
        }

        return (array) $config;
    }
}
