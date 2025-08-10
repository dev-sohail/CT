<?php
/**
 * Class IcvSoap
 *
 * A basic SOAP client wrapper for interacting with SOAP-based web services.
 */
class IcvSoap
{
    protected ?\SoapClient $client = null;
    protected string $wsdl;
    protected array $options;

    public function __construct(string $wsdl, array $options = [])
    {
        $this->wsdl = $wsdl;
        $this->options = $options;
        $this->initialize();
    }

    /**
     * Initialize the SOAP client.
     */
    protected function initialize(): void
    {
        try {
            $this->client = new \SoapClient($this->wsdl, $this->options);
        } catch (\SoapFault $e) {
            throw new \RuntimeException("SOAP Client initialization failed: " . $e->getMessage());
        }
    }

    /**
     * Call a SOAP method.
     */
    public function call(string $method, array $params = []): mixed
    {
        if (!$this->client) {
            throw new \RuntimeException("SOAP client not initialized.");
        }

        try {
            return $this->client->__soapCall($method, [$params]);
        } catch (\SoapFault $e) {
            throw new \RuntimeException("SOAP call to '{$method}' failed: " . $e->getMessage());
        }
    }

    /**
     * Get the underlying SOAP client instance.
     */
    public function getClient(): ?\SoapClient
    {
        return $this->client;
    }
}
