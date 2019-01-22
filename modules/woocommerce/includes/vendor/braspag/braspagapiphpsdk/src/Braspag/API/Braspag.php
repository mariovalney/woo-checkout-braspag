<?php
namespace Braspag\API;

use Braspag\API\Merchant;
use Braspag\API\Request\CreateSaleRequest;
use Braspag\API\Request\QuerySaleRequest;
use Braspag\API\Request\UpdateSaleRequest;
use Braspag\API\Request\QueryRecurrentPaymentRequest;

/**
 * The Braspag SDK front-end;
 */
class Braspag
{

    private $merchant;

    private $environment;

    /**
     * Create an instance of Braspag choosing the environment where the
     * requests will be send
     *
     * @param
     *            \Braspag\API\Merchant merchant
     *            The merchant credentials
     * @param
     *            \Braspag\API\Environment environment
     *            The environment: {@link Environment::production()} or
     *            {@link Environment::sandbox()}
     */
    public function __construct(Merchant $merchant, Environment $environment = null)
    {
        if ($environment == null) {
            $environment = Environment::production();
        }

        $this->merchant = $merchant;
        $this->environment = $environment;
    }

    /**
     * Send the Sale to be created and return the Sale with tid and the status
     * returned by Braspag.
     *
     * @param \Braspag\API\Sale $sale
     *            The preconfigured Sale
     * @return \Braspag\API\Sale The Sale with authorization, tid, etc. returned by Braspag.
     * @throws BraspagRequestException if anything gets wrong.
     */
    public function createSale(Sale $sale)
    {
        $createSaleRequest = new CreateSaleRequest($this->merchant, $this->environment);

        return $createSaleRequest->execute($sale);
    }

    /**
     * Query a Sale on Braspag by paymentId
     *
     * @param string $paymentId
     *            The paymentId to be queried
     * @return \Braspag\API\Sale The Sale with authorization, tid, etc. returned by Braspag.
     * @throws BraspagRequestException if anything gets wrong.
     */
    public function getSale($paymentId)
    {
        $querySaleRequest = new QuerySaleRequest($this->merchant, $this->environment);

        return $querySaleRequest->execute($paymentId);
    }

    /**
     * Query a RecurrentPayment on Braspag by RecurrentPaymentId
     *
     * @param string $recurrentPaymentId
     *            The RecurrentPaymentId to be queried
     * @return \Braspag\API\RecurrentPayment The RecurrentPayment with authorization, tid, etc. returned by Braspag.
     * @throws BraspagRequestException if anything gets wrong.
     */
    public function getRecurrentPayment($recurrentPaymentId)
    {
        $queryRecurrentPaymentRequest = new queryRecurrentPaymentRequest($this->merchant, $this->environment);

        return $queryRecurrentPaymentRequest->execute($recurrentPaymentId);
    }

    /**
     * Cancel a Sale on Braspag by paymentId and speficying the amount
     *
     * @param string $paymentId
     *            The paymentId to be queried
     * @param integer $amount
     *            Order value in cents
     * @return \Braspag\API\Sale The Sale with authorization, tid, etc. returned by Braspag.
     * @throws BraspagRequestException if anything gets wrong.
     */
    public function cancelSale($paymentId, $amount = null)
    {
        $updateSaleRequest = new UpdateSaleRequest('void', $this->merchant, $this->environment);

        $updateSaleRequest->setAmount($amount);

        return $updateSaleRequest->execute($paymentId);
    }

    /**
     * Capture a Sale on Braspag by paymentId and specifying the amount and the
     * serviceTaxAmount
     *
     * @param string $paymentId
     *            The paymentId to be captured
     * @param integer $amount
     *            Amount of the authorization to be captured
     * @param integer $serviceTaxAmount
     *            Amount of the authorization should be destined for the service
     *            charge
     * @return \Braspag\API\Payment The captured Payment.
     *
     * @throws BraspagRequestException if anything gets wrong.
     */
    public function captureSale($paymentId, $amount = null, $serviceTaxAmount = null)
    {
        $updateSaleRequest = new UpdateSaleRequest('capture', $this->merchant, $this->environment);

        $updateSaleRequest->setAmount($amount);
        $updateSaleRequest->setServiceTaxAmount($serviceTaxAmount);

        return $updateSaleRequest->execute($paymentId);
    }
}
