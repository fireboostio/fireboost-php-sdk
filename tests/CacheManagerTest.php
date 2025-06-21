<?php

namespace FireboostIO\SDK\Tests;

use FireboostIO\SDK\CacheManager;
use FireboostIO\Api\FireboostApi;
use FireboostIO\Model\SetInput;
use FireboostIO\Model\LoginInput;
use FireboostIO\ApiException;
use FireboostIO\SDK\Adapter\TokenStorageAdapterInterface;
use Fireboostio\Encryptor\ApiKeyExtractor;
use Fireboostio\Encryptor\CredentialExtractor;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    /**
     * @var FireboostApi|\PHPUnit\Framework\MockObject\MockObject
     */
    private $apiMock;

    /**
     * @var TokenStorageAdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $adapterMock;

    /**
     * @var ApiKeyExtractor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $apiKeyExtractorMock;

    /**
     * @var CredentialExtractor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $credentialExtractorMock;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the FireboostApi
        $this->apiMock = $this->createMock(FireboostApi::class);

        // Configure the API mock to expect setHost calls
        $configMock = $this->createMock(\FireboostIO\Configuration::class);
        $this->apiMock->method('getConfig')->willReturn($configMock);

        // Create a mock for the TokenStorageAdapter
        $this->adapterMock = $this->createMock(TokenStorageAdapterInterface::class);
        $this->adapterMock->method('hasToken')->willReturn(true);
        $this->adapterMock->method('getToken')->willReturn('test-token');

        // Create a mock for the ApiKeyExtractor
        $this->apiKeyExtractorMock = $this->createMock(ApiKeyExtractor::class);
        $this->apiKeyExtractorMock->method('getApiKeyPayload')
            ->with('test-api-key')
            ->willReturn(['project' => 'testproject']);

        // Create a mock for the CredentialExtractor
        $this->credentialExtractorMock = $this->createMock(CredentialExtractor::class);
        $this->credentialExtractorMock->method('getLoginInputData')
            ->with('test-api-key')
            ->willReturn(['encripted_api_key' => 'test-encrypted-key']);

        // Create a CacheManager instance with the mock adapter
        $this->cacheManager = new CacheManager($this->adapterMock, 'test-api-key');

        // Replace the internal API with our mock
        $reflectionApi = new \ReflectionProperty(CacheManager::class, 'api');
        $reflectionApi->setAccessible(true);
        $reflectionApi->setValue($this->cacheManager, $this->apiMock);

        // Replace the internal ApiKeyExtractor with our mock
        $reflectionExtractor = new \ReflectionProperty(CacheManager::class, 'apiKeyExtractor');
        $reflectionExtractor->setAccessible(true);
        $reflectionExtractor->setValue($this->cacheManager, $this->apiKeyExtractorMock);

        // Replace the internal CredentialExtractor with our mock
        $reflectionCredential = new \ReflectionProperty(CacheManager::class, 'credentialExtractor');
        $reflectionCredential->setAccessible(true);
        $reflectionCredential->setValue($this->cacheManager, $this->credentialExtractorMock);
    }

    /**
     * Test the saveCache method
     */
    public function testSaveCache()
    {
        // Set up the expected response
        $expectedResponse = ['success' => true];

        // Set up the mock to expect setCache call and return the expected response
        $this->apiMock->expects($this->once())
            ->method('setCache')
            ->with($this->callback(function ($setInput) {
                return $setInput instanceof SetInput
                    && $setInput->getCacheKey() === 'test-key'
                    && $setInput->getContent() === 'test-content'
                    && $setInput->getIsPublic() === false;
            }))
            ->willReturn($expectedResponse);

        // Call the method
        $response = $this->cacheManager->saveCache('test-key', 'test-content');

        // Assert the response
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Test the readCache method
     */
    public function testReadCache()
    {
        // Set up the expected response
        $expectedResponse = ['content' => 'test-content'];

        // Set up the mock to expect getCache call and return the expected response
        $this->apiMock->expects($this->once())
            ->method('getCache')
            ->with('test-key')
            ->willReturn($expectedResponse);

        // Call the method
        $response = $this->cacheManager->readCache('test-key');

        // Assert the response
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Test the deleteCache method
     */
    public function testDeleteCache()
    {
        // Set up the mock to expect deleteCache call
        $this->apiMock->expects($this->once())
            ->method('deleteCache')
            ->with('test-key');

        // Call the method
        $this->cacheManager->deleteCache('test-key');
    }

    /**
     * Test the readPublicCache method
     */
    public function testReadPublicCache()
    {
        // Set up the expected response
        $expectedResponse = ['content' => 'public-content'];

        // Set up the mock to expect publicGetCache call and return the expected response
        $this->apiMock->expects($this->once())
            ->method('publicGetCache')
            ->with('test-key')
            ->willReturn($expectedResponse);

        // Call the method
        $response = $this->cacheManager->readPublicCache('test-key');

        // Assert the response
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Test error handling
     */
    public function testErrorHandling()
    {
        // Create an ApiException
        $exception = new ApiException('Test error');

        // Set up the mock to throw an exception
        $this->apiMock->expects($this->once())
            ->method('getCache')
            ->willThrowException($exception);

        // Expect an exception
        $this->expectException(ApiException::class);

        // Call the method
        $this->cacheManager->readCache('test-key');
    }

    /**
     * Test the getStatistics method
     */
    public function testGetStatistics()
    {
        // Expected statistics response from the API
        $expectedStats = [
            'reads' => 100,
            'writes' => 50,
            'total_size' => 1024,
            'average_response_time' => 0.05
        ];

        // Set up the mock to expect getTrackingData call and return the expected statistics
        $this->apiMock->expects($this->once())
            ->method('getTrackingData')
            ->willReturn($expectedStats);

        // Call the method
        $stats = $this->cacheManager->getStatistics();

        // Assert the response
        $this->assertEquals($expectedStats, $stats);
    }
}
